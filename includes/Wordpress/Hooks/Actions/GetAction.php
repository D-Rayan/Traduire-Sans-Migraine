<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\Object\Action;


if (!defined("ABSPATH")) {
    exit;
}

class GetAction
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
        add_action("wp_ajax_traduire-sans-migraine_editor_get_action", [$this, "getAction"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getAction()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["actionId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $actionId = $_GET["actionId"];
        $action = Action::loadById($actionId);
        if (!$action) {
            wp_send_json_error([], 400);
            wp_die();
        }
        echo json_encode(["success" => true, "data" => $action->toArray()]);
        wp_die();
    }
}

$GetAction = new GetAction();
$GetAction->init();