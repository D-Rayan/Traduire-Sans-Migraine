<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Pages\Menu\Bulk\Dictionary;
use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class AddWordToDictionary {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_add_word_to_dictionary", [$this, "addWordToDictionary"]);
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

    public function addWordToDictionary() {
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
        if (!isset($_POST["langTo"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language to is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $langFrom = $_POST["langFrom"];
        $entry = trim($_POST["entry"]);
        $result = trim($_POST["result"]);
        $langTo = $_POST["langTo"];
        if (empty($langFrom) || empty($entry) || empty($result) || empty($langTo)) {
            wp_send_json_error([
                "message" => TextDomain::__("The fields are empty"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $client = Client::getInstance();
        $response = $client->addWordToDictionary($entry, $result, $langFrom, $langTo);
        if ($response === false) {
            wp_send_json_error([
                "message" => TextDomain::__("The word has not been added"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $Dictionary = new Dictionary();
        ob_start();
        $Dictionary->renderRow($langFrom, $entry, $result, $response, $langTo);
        $updatedRow = ob_get_clean();
        ob_start();
        $Dictionary->renderNewRow($langFrom, $langTo);
        $newRow = ob_get_clean();
        wp_send_json_success([
            "message" => TextDomain::__("The word has been added"),
            "title" => "",
            "logo" => "loutre_docteur_no_shadow.png",
            "updatedRow" => $updatedRow,
            "newRow" => $newRow
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