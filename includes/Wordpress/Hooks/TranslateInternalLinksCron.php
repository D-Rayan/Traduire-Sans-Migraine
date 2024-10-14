<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Settings;
use WP_Query;

if (!defined("ABSPATH")) {
    exit;
}

/**
 * @TODO : Create table with ID post, and wrongs urls present on the content (I.E url not translated on the current language of the post)
 * @TODO : Add hook on update content to update entry of the post and the wrongs urls
 * @TODO : Add hook on status transition to update all entry with the url of the post to be executed on next cron
 * @TODO : Everytime the cron is executed, we  take the entry that should be executed
 * @TODO : If the urls are empty we delete this entry
 * @TODO : Else we just update the entry has executed = 0
 */
class TranslateInternalLinksCron
{
    public function __construct()
    {
    }


    public function getState()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        $missing = count($this->getPosts(true, false));
        $done = count($this->getPosts(false, false));
        wp_send_json_success([
            "missing" => $missing,
            "done" => $done
        ]);
    }

    private function getPosts($fetchedNextOnes, $onlyOne)
    {
        $lastExecutedTimePost = get_option("traduire_sans_migraine_cron_last_updated_time", null);
        if (!$fetchedNextOnes && $lastExecutedTimePost === null) {
            return [];
        }
        $args = [
            "post_type" => [
                "post",
                "page"
            ],
            "post_status" => [
                "publish",
                "draft",
                "pending",
                "private",
                "future",
            ],
            "suppress_filters" => true,
            "update_post_term_cache" => false,
            "update_post_meta_cache" => false,
            "cache_results" => false,
            "fields" => "ids",
            "posts_per_page" => $onlyOne ? 1 : -1,
            'lang' => ''
        ];
        if ($fetchedNextOnes) {
            $args["date_query"] = [
                "column" => "post_modified",
                "after" => $lastExecutedTimePost
            ];
        } else {
            $args["date_query"] = [
                "column" => "post_modified",
                "before" => $lastExecutedTimePost
            ];
        }
        $wpQuery = new WP_Query($args);
        return $wpQuery->posts;
    }

    public function init()
    {
        $this->loadCron();
    }

    public function loadCron()
    {
        if (is_admin()) {
            add_action("transition_post_status", [$this, "handleNewPublication"], 2);
            add_action("wp_ajax_traduire-sans-migraine_get_cron_state", [$this, "getState"]);
            add_action("traduire_sans_migraine_cron_translate_internal_links", [$this, "translateInternalLinksCron"]);
            /*add_filter('cron_schedules', function ($schedules) {
                $schedules['every_minute'] = array(
                    'interval' => 60,
                    'display' => __('Every minute')
                );
            });
            wp_schedule_event(time(), 'every_minute', "traduire_sans_migraine_cron_translate_internal_links");*/
        }
    }

    function handleNewPublication($new_status, $old_status, $post)
    {
        if ($new_status == 'publish' && $old_status != 'publish') {
            $author = "foobar";
            $message = "We wanted to notify you a new post has been published.";
            wp_mail($author, "New Post Published", $message);
        }
    }

    public function translateInternalLinksCron()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["autoTranslateLinks"])) {
            return;
        }
        $posts = $this->getPosts(true, true);
        if (empty($posts)) {
            return;
        }
        $post = $posts[0];
        $this->translateInternalLinks($post);
        update_option("traduire_sans_migraine_cron_last_updated_time", $post->post_modified, false);
    }

    private function translateInternalLinks($post)
    {
        global $tsm;
        $content = $post->post_content;
        $language = $tsm->getPolylangManager()->getLanguageForPost($post->ID);
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        foreach ($languages as $slug => $ignored) {
            if ($slug === $language) {
                continue;
            }
            $content = $tsm->getLinkManager()->translateInternalLinks($content, $slug, $language);
            $this->handleElementor($post->ID, $slug, $language);
        }
        wp_update_post([
            "ID" => $post->ID,
            "post_content" => $content,
            "post_modified" => current_time("mysql")
        ]);
    }

    private function handleElementor($postId, $codeTo, $codeFrom)
    {
        global $tsm;

        if (is_plugin_active("elementor/elementor.php")) {
            $postMetas = get_post_meta($postId);
            foreach ($postMetas as $key => $value) {
                if (strstr($key, "elementor")) {
                    $valueKey = $value[0];
                    if ($this->isSerialized($valueKey)) {
                        continue;
                    }
                    $newValueKey = $tsm->getLinkManager()->translateInternalLinks($valueKey, $codeFrom, $codeTo);
                    if ($newValueKey !== $valueKey) {
                        if ($this->isJson($newValueKey)) {
                            $newValueKey = wp_slash($newValueKey);
                        }
                        update_post_meta($postId, $key, $newValueKey);
                    }
                }
            }
        }
    }

    private function isSerialized($string)
    {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }

    private function isJson($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }
}

$TranslateInternalLinksCron = new TranslateInternalLinksCron();
$TranslateInternalLinksCron->init();
