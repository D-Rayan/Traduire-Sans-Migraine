<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetPostTranslated {
    private $languageManager;
    public function __construct()
    {
        $this->languageManager = new LanguageManager();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_get_post_translated", [$this, "getTranslatedPostId"]);
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

    public function getTranslatedPostId() {
        if (!isset($_GET["post_id"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find your post. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["language"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the language. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $language = $_GET["language"];
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($postId, $language);
        echo json_encode(["success" => true, "data" => $translatedPostId]);
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