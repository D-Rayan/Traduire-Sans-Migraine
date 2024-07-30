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

class GetPostNotifications {
    private $languageManager;
    public function __construct()
    {
        $this->languageManager = new LanguageManager();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_get_post_notifications", [$this, "getPostNotifications"]);
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

    public function getPostNotifications() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_editor_get_post_notifications")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["post_id"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find your post. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $hasBeenTranslatedByTsm = get_post_meta($postId, "_has_been_translated_by_tsm", true);
        $notifications = [];
        if ($hasBeenTranslatedByTsm) {
            $linkTraduireSansMigrainePractices = TextDomain::__("https://www.seo-sans-migraine.fr/astuces-traduction-seo");
            $post = get_post($postId);
            $languageManager = new LanguageManager();
            $linkManager = new LinkManager();
            $currentLanguagePost = $languageManager->getLanguageManager()->getLanguageForPost($postId);
            $internalLinks = $linkManager->getIssuedInternalLinks($post->post_content, get_post_meta($postId, "_translated_by_tsm_from", true), $currentLanguagePost);
            $translatable = $internalLinks["translatable"];
            if (count($translatable) > 0) {
                $notifications[] = [
                    "debug" => $internalLinks,
                    "title" => TextDomain::__("Translated by Traduire Sans Migraine"),
                    "message" => TextDomain::__("You're on a content translated by us. If you want to know the best SEO practices, click <a target='_blank' href='%s'>here</a>.", $linkTraduireSansMigrainePractices) . "<br/>" . TextDomain::_n("We have detected %s internal link that is not translated.", "We have detected %s internal links that are not translated.", count($translatable), count($translatable)),
                    "logo" => "loutre_docteur_no_shadow.png",
                    "type" => "success",
                    "persist" => true,
                    "displayDefault" => true,
                    "buttons" => [
                        [
                            "label" => TextDomain::__("translateInternalLinksButton"),
                            "type" => "primary",
                            "action" => "translateInternalLinks",
                            "wpNonce" => wp_create_nonce("traduire-sans-migraine_editor_translate_internal_links"),
                        ]
                    ],
                ];
            } else {
                $notifications[] = [
                    "title" => TextDomain::__("Translated by Traduire Sans Migraine"),
                    "message" => TextDomain::__("You're on a content translated by us. If you want to know the best SEO practices, click <a target='_blank' href='%s'>here</a>", $linkTraduireSansMigrainePractices),
                    "logo" => "loutre_docteur_no_shadow.png",
                    "type" => "success",
                    "persist" => true,
                    "displayDefault" => false,
                    "buttons" => [],
                ];
            }
        }
        echo json_encode(["success" => true, "data" => $notifications]);
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