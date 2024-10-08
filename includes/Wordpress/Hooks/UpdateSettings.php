<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateSettings
{
    public function __construct()
    {
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
        foreach ($_POST["settings"] as $key => $value) {
            if (!is_bool($value)) {
                continue;
            }
            if (isset($settings[$key])) {
                $settings[$key] = $value;
            }
        }
        foreach ($settings as $key => $value) {
            if (!isset($value["enabled"])) {
                continue;
            }
            $settings[$key] = $value["enabled"];
        }
        $tsm->getSettings()->saveSettings($settings);
        wp_send_json_success(["settings" => $settings]);
        wp_die();
    }
}

$UpdateSettings = new UpdateSettings();
$UpdateSettings->init();