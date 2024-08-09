<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class TranslateInternalLinks {
    private $languageManager;
    private $linkManager;
    public function __construct()
    {
        $this->languageManager = new LanguageManager();
        $this->linkManager = new LinkManager();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_translate_internal_links", [$this, "translateInternalLinks"]);
    }

    public function loadHooks() {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }
    public function init() {
        $this->loadHooks();
    }

    private function handleElementor($postId, $codeTo, $codeFrom) {
        if (is_plugin_active("elementor/elementor.php")) {
            $postMetas = get_post_meta($postId);
            foreach ($postMetas as $key => $value) {
                if (strstr($key, "elementor")) {
                    $valueKey = $value[0];
                    if ($this->is_serialized($valueKey)) {
                        continue;
                    }
                    $newValueKey = $this->linkManager->translateInternalLinks($valueKey, $codeFrom, $codeTo);
                    if ($newValueKey !== $valueKey) {
                        if ($this->is_json($newValueKey)) {
                            $newValueKey = wp_slash($newValueKey);
                        }
                        update_post_meta($postId, $key, $newValueKey);
                    }
                }
            }
        }
    }

    private function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function is_serialized($string) {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }

    public function translateInternalLinks() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_editor_translate_internal_links")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["post_id"]) || !get_post_meta($_GET["post_id"], "_has_been_translated_by_tsm", true)) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $post = get_post($_GET["post_id"]);
        $content = $post->post_content;
        $language = $this->languageManager->getLanguageManager()->getLanguageForPost($post->ID);
        $languages = $this->languageManager->getLanguageManager()->getLanguagesActives();
        foreach ($languages as $slug => $ignored) {
            if ($slug === $language) {
                continue;
            }
            $content = $this->linkManager->translateInternalLinks($content, $slug, $language);
            $this->handleElementor($post->ID, $slug, $language);
        }
        wp_update_post([
            "ID" => $post->ID,
            "post_content" => $content
        ]);
        wp_send_json_success([]);
        wp_die();
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}