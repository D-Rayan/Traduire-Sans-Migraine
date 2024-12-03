<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Woocommerce;

use TraduireSansMigraine\Settings;

if (!defined("ABSPATH")) {
    exit;
}

class HandleNewDefaultLanguage
{
    public function __construct()
    {
    }

    public function init()
    {
        add_action("wp_ajax_traduire-sans-migraine_handle_new_default_language", [$this, "handleNewDefaultLanguage"]);
    }

    public function handleNewDefaultLanguage()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["defaultCode"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        do_action("tsm-set-default-language", $_GET["defaultCode"]);
        wp_send_json_success([]);
        wp_die();
    }
}

$HandleNewDefaultLanguage = new HandleNewDefaultLanguage();
$HandleNewDefaultLanguage->init();