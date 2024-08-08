<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class DeleteWordToDictionary {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_delete_word_to_dictionary", [$this, "deleteWordToDictionary"]);
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

    public function deleteWordToDictionary() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_delete_word_to_dictionary")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["id"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The id is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $id = $_GET["id"];
        $client = new Client();
        $response = $client->deleteWordFromDictionary($id);
        if ($response === false) {
            wp_send_json_error([
                "message" => TextDomain::__("The word has not been deleted"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        wp_send_json_success([
            "message" => TextDomain::__("The word has been deleted"),
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