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

class AddNewLanguage {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_add_new_language", [$this, "addNewLanguage"]);
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

    public function addNewLanguage() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine_add_new_language")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["language"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $languageManager = new LanguageManager();
        if ($languageManager->getLanguageManager()->addLanguage($_POST["language"])) {
            wp_send_json_success([
                "message" => TextDomain::__("The language has been added"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ]);
        } else {
            wp_send_json_error([
                "message" => TextDomain::__("The language has not been added"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
        }
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