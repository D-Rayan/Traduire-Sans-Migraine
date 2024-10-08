<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Action;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class AddToQueue {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_add_to_queue", [$this, "addToQueue"]);
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

    public function addToQueue() {
        global $tsm;
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
            ], 400);
            wp_die();
        }
        if (!isset($_POST["postId"]) || !is_numeric($_POST["postId"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The post id is not valid"),
            ], 400);
            wp_die();
        }
        if (!isset($_POST["languageTo"]) || !is_string($_POST["languageTo"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language to is not valid"),
            ], 400);
            wp_die();
        }
        $languageTo = $_POST["languageTo"];
        $postId = intval($_POST["postId"]);
        $languageFrom = $tsm->getPolylangManager()->getLanguageForPost($postId);
        if ($languageFrom === $languageTo) {
            wp_send_json_error([
                "message" => TextDomain::__("The post is already in the target language"),
            ], 400);
            wp_die();
        }
        $existingAction = Action::loadByPostId($postId, $languageTo);
        if ($existingAction && $existingAction->willBeProcessing()) {
            wp_send_json_error([
                "message" => TextDomain::__("The action is already in the queue"),
            ], 400);
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