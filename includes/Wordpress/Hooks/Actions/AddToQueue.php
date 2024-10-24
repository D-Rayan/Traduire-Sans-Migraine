<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Object\Action;


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
        if (!isset($_POST["postId"]) || !is_numeric($_POST["postId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        if (!isset($_POST["languageTo"]) || !is_string($_POST["languageTo"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $languageTo = $_POST["languageTo"];
        $postId = intval($_POST["postId"]);
        $languageFrom = $tsm->getPolylangManager()->getLanguageSlugForPost($postId);
        if ($languageFrom === $languageTo) {
            wp_send_json_error([
                "message" => seoSansMigraine_returnErrorForImpossibleReasons()
            ], 400);
            wp_die();
        }
        $existingAction = Action::loadByPostId($postId, $languageTo);
        if ($existingAction && $existingAction->willBeProcessing()) {
            $existingAction->setOrigin(isset($_POST["havePriority"]) ? DAOActions::$ORIGINS["EDITOR"] : DAOActions::$ORIGINS["QUEUE"]);
            $existingAction->save();
            wp_send_json_success([
                "ID" => $existingAction->getID()
            ]);
            wp_die();
        }
        $action = new Action([
            "postId" => $postId,
            "slugTo" => $languageTo,
            "origin" => isset($_POST["havePriority"]) ? DAOActions::$ORIGINS["EDITOR"] : DAOActions::$ORIGINS["QUEUE"]
        ]);
        $action->save();
        wp_send_json_success([
            "ID" => $action->getID()
        ]);
        wp_die();
    }
}

$AddToQueue = new AddToQueue();
$AddToQueue->init();