<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;

if (!defined("ABSPATH")) {
    exit;
}

class GetInternalsLinksState
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
        add_action("wp_ajax_traduire-sans-migraine_get_internals_links_state", [$this, "getInternalsLinksState"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getInternalsLinksState()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        $state = CronInitializeInternalLinks::getOption();
        wp_send_json_success([
            "cron" => $state,
            "countFixable" => DAOInternalsLinks::countFixable(),
            "countAll" => DAOInternalsLinks::countAll(),
            "countFixed" => DAOInternalsLinks::countFixed(),
            "nextRun" => CronInitializeInternalLinks::getNextTimeRun()
        ]);
        wp_die();
    }
}

$GetInternalsLinksState = new GetInternalsLinksState();
$GetInternalsLinksState->init();