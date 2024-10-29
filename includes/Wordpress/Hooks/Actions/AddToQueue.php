<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;


if (!defined("ABSPATH")) {
    exit;
}

class AddToQueue
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
        add_action("wp_ajax_traduire-sans-migraine_add_to_queue", [$this, "addToQueue"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function addToQueue()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["objectId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        if (!isset($_POST["objectType"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        if (!isset($_POST["languageTo"]) || !is_string($_POST["languageTo"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $languageTo = $_POST["languageTo"];
        $objectId = $_POST["objectId"];
        $actionType = $_POST["objectType"];
        if (!in_array($actionType, DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $existingAction = AbstractAction::loadByObjectId($objectId, $languageTo, $actionType);
        if ($existingAction && $existingAction->willBeProcessing()) {
            $existingAction->setOrigin(isset($_POST["havePriority"]) ? DAOActions::$ORIGINS["EDITOR"] : DAOActions::$ORIGINS["QUEUE"]);
            $existingAction->save();
            wp_send_json_success([
                "ID" => $existingAction->getID()
            ]);
            wp_die();
        }
        $action = AbstractAction::getInstance([
            "objectId" => $objectId,
            "slugTo" => $languageTo,
            "origin" => isset($_POST["havePriority"]) ? DAOActions::$ORIGINS["EDITOR"] : DAOActions::$ORIGINS["QUEUE"],
            "actionType" => $actionType
        ]);
        $action->save();
        $enrichedAction = apply_filters("tsm-enrich-actions", $action);
        wp_send_json_success($enrichedAction);
        wp_die();
    }
}

$AddToQueue = new AddToQueue();
$AddToQueue->init();