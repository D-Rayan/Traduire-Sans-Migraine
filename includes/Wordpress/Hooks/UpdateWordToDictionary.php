<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateWordToDictionary {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_update_word_to_dictionary", [$this, "updateWordToDictionary"]);
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

    public function updateWordToDictionary() {
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["langFrom"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language from is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["entry"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The entry is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["result"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The result is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["id"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The id is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $langFrom = $_POST["langFrom"];
        $entry = trim($_POST["entry"]);
        $result = trim($_POST["result"]);
        $id = $_POST["id"];
        if (empty($langFrom) || empty($entry) || empty($result) || empty($id)) {
            wp_send_json_error([
                "message" => TextDomain::__("The fields are empty"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $client = Client::getInstance();
        $response = $client->updateWordToDictionary($id, $entry, $result, $langFrom);
        if ($response === false) {
            wp_send_json_error([
                "message" => TextDomain::__("The word has not been added"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        wp_send_json_success([
            "message" => TextDomain::__("The word has been updated"),
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