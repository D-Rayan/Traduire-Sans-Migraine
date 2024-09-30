<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\DAO\DAOQueue;
use TraduireSansMigraine\Wordpress\TextDomain;

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
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = DAOQueue::getItemsForQueue();
        foreach ($queue as $key => $item) {
            $translationMap = !empty($item["translationMap"]) ? unserialize($item["translationMap"]) : [];
            $slug = $item["slugTo"];
            $translationId = isset($translationMap[$slug]) ? $translationMap[$slug] : null;
            $translation = $translationId ? get_post($translationId) : null;
            $isTranslated = !empty($translation);
            $translationIsUpdated = $translation && $translation->post_modified > $item["post_modified"];
            $translationMap[$slug] = [
                "translation" => $isTranslated ? [
                    "ID" => $translation->ID,
                    "title" => $translation->post_title,
                ] : null,
                "translationIsUpdated" => $translationIsUpdated,
            ];
            $queue[$key]["post"] = [
                "ID" => $item["postId"],
                "post_title" => $item["post_title"],
                "post_author" => $item["post_author"],
                "post_status" => $item["post_status"],
                "translationMap" => $translationMap,
            ];
            unset($queue[$key]["postId"]);
            unset($queue[$key]["post_title"]);
            unset($queue[$key]["post_author"]);
            unset($queue[$key]["post_status"]);
            unset($queue[$key]["translationMap"]);
        }
        wp_send_json_success([
            "queue" => $queue,
        ]);
        wp_die();
    }
}
$GetQueue = new GetQueue();
$GetQueue->init();