<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Languages;

use Exception;

if (!defined("ABSPATH")) {
    exit;
}

class AddNewLanguage
{

    public function __construct()
    {
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function loadHooks()
    {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }

    public function loadHooksAdmin()
    {
        add_action("wp_ajax_traduire-sans-migraine_add_new_language", [$this, "addNewLanguage"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function addNewLanguage()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["language"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
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
                    wp_send_json_success([]);
                } else {
                    wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
                }
                wp_die();
            }
        } catch (Exception $e) {

        }
        if ($polylangManager->addLanguage($locale)) {
            $client->enableLanguage($slug);
            wp_send_json_success([]);
        } else {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
        }
        wp_die();
    }
}

$AddNewLanguage = new AddNewLanguage();
$AddNewLanguage->init();