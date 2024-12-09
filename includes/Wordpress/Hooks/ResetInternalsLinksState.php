<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;

if (!defined("ABSPATH")) {
    exit;
}

class ResetInternalsLinksState
{
    public function __construct() {}

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
        add_action("wp_ajax_traduire-sans-migraine_reset_internals_links_state", [$this, "resetInternalsLinksState"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function resetInternalsLinksState()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        CronInitializeInternalLinks::reset();
        wp_send_json_success([]);
        wp_die();
    }
}

$ResetInternalsLinksState = new ResetInternalsLinksState();
$ResetInternalsLinksState->init();
