<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateLanguageSettings {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_update_language_settings", [$this, "updateLanguageSettings"]);
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

    public function updateLanguageSettings() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine_update_language_settings")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["slug"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The slug is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $country = (isset($_POST["country"])) ? strtoupper(str_replace("_", "-", $_POST["country"])) : null;
        $formality = (isset($_POST["formality"])) ? $_POST["formality"] : null;
        $client = Client::getInstance();
        $response = $client->updateLanguageSettings($_POST["slug"], $formality, $country);
        if ($response === false) {
            wp_send_json_error([
                "message" => TextDomain::__("The language has not been updated"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        wp_send_json_success([
            "message" => TextDomain::__("The language has been updated"),
            "title" => "",
            "logo" => "loutre_docteur_no_shadow.png"
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