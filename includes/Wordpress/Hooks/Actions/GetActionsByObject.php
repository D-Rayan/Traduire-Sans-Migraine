<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;

if (!defined("ABSPATH")) {
    exit;
}

class GetActionsByObject
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
        if (!is_admin()) {
            return;
        }
        add_action("wp_ajax_traduire-sans-migraine_get_actions_by_object", [$this, "getActions"]);
    }

    public function getActions()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["objectId"]) || !isset($_GET["objectType"]) || !in_array($_GET["objectType"], DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        do_action("fetchTranslationsBackground");
        do_action("startNextProcess");
        $objectId = $_GET["objectId"];
        $objectType = $_GET["objectType"];
        $actions = DAOActions::getActionsByObjectId($objectId, $objectType);
        $actions = apply_filters("tsm-enrich-actions", $actions);
        $pausedAction = AbstractAction::getActionPaused();
        $pausedAction = apply_filters("tsm-enrich-actions", $pausedAction);
        echo json_encode(["success" => true, "data" => [
            "actions" => $actions,
            "actionPaused" => $pausedAction,
        ]]);
        wp_die();
    }
}

$GetActionsByObject = new GetActionsByObject();
$GetActionsByObject->init();