<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class RemoveFromQueue {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_remove_from_queue", [$this, "removeFromQueue"]);
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

    public function removeFromQueue() {
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["actionId"]) || !is_numeric($_GET["actionId"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The item id is not valid")
            ], 400);
            wp_die();
        }
        $actionId = $_GET["actionId"];
        $rowsDeleted = DAOActions::removeAction($actionId);
        if ($rowsDeleted !== 1) {
            wp_send_json_error([
                "message" => TextDomain::__("The item was not removed"),
            ], 400);
            wp_die();
        }
        wp_send_json_success([
        ]);
        wp_die();
    }
}
$RemoveFromQueue = new RemoveFromQueue();
$RemoveFromQueue->init();