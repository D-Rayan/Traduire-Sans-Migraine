<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class SendReasonsDeactivate {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_send_reasons_deactivate", [$this, "sendReason"]);
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

    public function sendReason() {
        global $tsm;
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["reason"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The reason is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $result = $tsm->getClient()->checkCredential();
        if (!$result) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not authenticate you. Please check the plugin settings."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $tsm->getClient()->sendReasonDeactivate([
            "reason" => $_POST["reason"]
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