<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;


if (!defined("ABSPATH")) {
    exit;
}

class GetQueue
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
        add_action("wp_ajax_traduire-sans-migraine_get_queue", [$this, "getQueue"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getQueue()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        do_action("fetchTranslationsBackground");
        do_action("startNextProcess");
        $queue = DAOActions::getActionsForQueue();
        $queue = apply_filters("tsm-enrich-actions", $queue);

        wp_send_json_success([
            "queue" => $queue,
        ]);
        wp_die();
    }
}

$GetQueue = new GetQueue();
$GetQueue->init();