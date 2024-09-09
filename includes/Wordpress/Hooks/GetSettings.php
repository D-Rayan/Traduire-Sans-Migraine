<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetSettings {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_settings", [$this, "getSettings"]);
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

    public function getSettings() {
        global $tsm;
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $settings = $tsm->getSettings()->getSettings();
        wp_send_json_success([
            "settings" => $settings
        ]);
        wp_die();
    }
}
$GetSettings = new GetSettings();
$GetSettings->init();