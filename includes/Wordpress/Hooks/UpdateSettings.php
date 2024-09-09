<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class UpdateSettings {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_update_settings", [$this, "updateSettings"]);
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

    public function updateSettings() {
        global $tsm;
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["settings"])) {
            wp_send_json_error([
                "message" => TextDomain::__("Missing parameters"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
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