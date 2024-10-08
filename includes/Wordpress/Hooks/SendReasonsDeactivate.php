<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

if (!defined("ABSPATH")) {
    exit;
}

class SendReasonsDeactivate
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
        add_action("wp_ajax_traduire-sans-migraine_send_reasons_deactivate", [$this, "sendReason"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function sendReason()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["reason"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $result = $tsm->getClient()->checkCredential();
        if (!$result) {
            wp_send_json_error(seoSansMigraine_returnLoginError(), 400);
            wp_die();
        }
        $tsm->getClient()->sendReasonDeactivate([
            "reason" => $_POST["reason"]
        ]);
        wp_die();
    }
}

$SendReasonsDeactivate = new SendReasonsDeactivate();
$SendReasonsDeactivate->init();