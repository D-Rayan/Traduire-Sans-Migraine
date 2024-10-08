<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Languages;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateLanguageSettings
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
        add_action("wp_ajax_traduire-sans-migraine_update_language_settings", [$this, "updateLanguageSettings"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function updateLanguageSettings()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["slug"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $slug = $_POST["slug"];
        $country = (isset($_POST["country"])) ? strtoupper(str_replace("_", "-", $_POST["country"])) : null;
        $formality = (isset($_POST["formality"])) ? $_POST["formality"] : null;
        $response = $tsm->getClient()->updateLanguageSettings($slug, $formality, $country);
        if ($response === false) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        if ($country) {
            $polylangManager = $tsm->getPolylangManager();
            $polylangManager->updateLanguage($slug, $country);
        }
        wp_send_json_success([]);
        wp_die();
    }
}

$UpdateLanguageSettings = new UpdateLanguageSettings();
$UpdateLanguageSettings->init();