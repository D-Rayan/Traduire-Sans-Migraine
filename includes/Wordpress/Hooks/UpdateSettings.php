<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Woocommerce\Woocommerce;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateSettings
{
    public function __construct()
    {
    }

    public function updateSettings()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["settings"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $settings = $tsm->getSettings()->getSettings();
        $woocommerceWasDisabled = !$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"]);
        foreach ($_POST["settings"] as $key => $value) {
            if (!is_bool($value)) {
                continue;
            }
            if (isset($settings[$key])) {
                $settings[$key] = $value && $settings[$key]["available"];
            }
        }
        foreach ($settings as $key => $value) {
            if (!isset($value["enabled"])) {
                continue;
            }
            $settings[$key] = $value["enabled"] && $value["available"];
        }
        $tsm->getSettings()->saveSettings($settings);
        if ($woocommerceWasDisabled && $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])) {
            Woocommerce::init();
            do_action("traduire-sans-migraine_enable_woocommerce");
        }
        wp_send_json_success(["settings" => $settings, "disabled" => $woocommerceWasDisabled, "enabled" => $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])]);
        wp_die();
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
        add_action("wp_ajax_traduire-sans-migraine_update_settings", [$this, "updateSettings"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }
}

$UpdateSettings = new UpdateSettings();
$UpdateSettings->init();