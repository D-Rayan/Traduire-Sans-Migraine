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

class UpdateTranslations {
    private $languageManager;
    public function __construct()
    {
        $this->languageManager = new LanguageManager();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_update_translations", [$this, "updateTranslations"]);
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

    public function updateTranslations()
    {
        if (!isset($_GET["wp_nonce"]) || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_editor_update_translations")) {
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
        $post = get_post($postId);
        $StartTranslation = StartTranslation::getInstance();
        $translations = $this->languageManager->getLanguageManager()->getAllTranslationsPost($postId);
        $responses = [];
        foreach ($translations as $langTo => $translation) {
            if (!empty($translation["postId"]) && $translation["postId"] != $postId) {
                $responses[] = $StartTranslation->startTranslateExecute($post, $langTo);
            }
        }
        wp_send_json_success([
            "message" => TextDomain::__("Translations will be soon updated"),
            "title" => "",
            "logo" => "loutre_docteur_no_shadow.png",
            "responses" => $responses
        ]);
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