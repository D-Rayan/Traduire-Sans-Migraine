<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Queue;
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
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["postId"]) || !is_numeric($_POST["postId"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The post id is not valid"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["languageTo"]) || !is_string($_POST["languageTo"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language to is not valid"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $languageTo = $_POST["languageTo"];
        $postId = intval($_POST["postId"]);
        $languageFrom = $tsm->getPolylangManager()->getLanguageForPost($postId);
        if ($languageFrom === $languageTo) {
            wp_send_json_error([
                "message" => TextDomain::__("The post is already in the target language"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        if ($queue->isAlreadyInQueue($postId, $languageTo)) {
            wp_send_json_error([
                "message" => TextDomain::__("The post is already in the queue"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queueId = $queue->addToQueue($postId, $languageTo);
        if ($queue->getState() === "idle") {
            // $queue->startNextProcess();
        }
        wp_send_json_success([
            "ID" => $queueId
        ]);
        wp_die();
    }
}
$AddToQueue = new AddToQueue();
$AddToQueue->init();