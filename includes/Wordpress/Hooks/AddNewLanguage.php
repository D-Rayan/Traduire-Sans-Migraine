<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
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
        global $tsm;
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
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
        $locale = $_POST["language"];
        $polylangManager = $tsm->getPolylangManager();
        $slug = substr($locale, 0, 2);
        $client = $tsm->getClient();
        try {
            $languages = $polylangManager->getLanguagesActives();
            if (isset($languages[$slug])) {
                if ($client->enableLanguage($slug)) {
                    wp_send_json_success([
                        "message" => TextDomain::__("The language has been enabled"),
                        "title" => "",
                        "logo" => "loutre_docteur_no_shadow.png"
                    ]);
                } else {
                    wp_send_json_error([
                        "message" => TextDomain::__("The language has not been enabled"),
                        "title" => "",
                        "logo" => "loutre_docteur_no_shadow.png"
                    ], 400);
                }
                wp_die();
            }
        } catch (\Exception $e) {

        }
        if ($polylangManager->addLanguage($locale)) {
            $client->enableLanguage($slug);
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

$AddNewLanguage = new AddNewLanguage();
$AddNewLanguage->init();