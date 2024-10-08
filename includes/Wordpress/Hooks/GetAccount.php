<?php

namespace TraduireSansMigraine\Wordpress\Hooks;



if (!defined("ABSPATH")) {
    exit;
}

class GetAccount {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_account", [$this, "getAccount"]);
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

    public function getAccount() {
        global $tsm;
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        $account = $tsm->getClient()->getAccount();
        $redirect = $tsm->getClient()->getRedirect();
        wp_send_json_success([
            "account" => $account,
            "redirect" => $redirect,
            "token" => $tsm->getSettings()->getToken()
        ]);
        wp_die();
    }
}
$GetAccount = new GetAccount();
$GetAccount->init();