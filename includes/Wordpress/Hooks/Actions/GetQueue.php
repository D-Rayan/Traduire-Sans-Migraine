<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Queue;


if (!defined("ABSPATH")) {
    exit;
}

class GetQueue {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_queue", [$this, "getQueue"]);
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

    public function getQueue() {
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        do_action("fetchTranslationsBackground");
        do_action("startNextProcess");
        wp_send_json_success([
            "queue" => Queue::getInstance()->getActionsEnriched(),
        ]);
        wp_die();
    }
}
$GetQueue = new GetQueue();
$GetQueue->init();