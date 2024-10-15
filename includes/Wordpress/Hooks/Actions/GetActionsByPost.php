<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Actions;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Object\Action;

if (!defined("ABSPATH")) {
    exit;
}

class GetActionsByPost
{
    private $translationMap = [];
    private $post;

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
        add_action("wp_ajax_traduire-sans-migraine_get_actions_by_post", [$this, "getActions"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getActions()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["postId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        do_action("fetchTranslationsBackground");
        do_action("startNextProcess");
        $postId = $_GET["postId"];
        $this->post = get_post($postId, ARRAY_A);
        if (empty($this->post)) {
            wp_send_json_error([
                "message" => "post_not_found"
            ], 404);
            wp_die();
        }
        $actions = [];
        foreach (DAOActions::getActionsByPostId($postId) as $dataAction) {
            $actions[] = $this->enrichedAction($dataAction);
        }
        $pausedAction = Action::getActionPaused();
        echo json_encode(["success" => true, "data" => [
            "actions" => $actions,
            "actionPaused" => empty($pausedAction) ? null : $pausedAction->toArray(true),
        ]]);
        wp_die();
    }

    private function enrichedAction($dataAction)
    {
        $action = new Action($dataAction);
        $data = $action->toArray(true);
        $data["post"] = [
            "ID" => $this->post["ID"],
            "post_title" => $this->post["post_title"],
            "post_author" => $this->post["post_author"],
            "post_status" => $this->post["post_status"],
            "translationMap" => $this->getTranslationMap(),
        ];

        return $data;
    }

    private function getTranslationMap()
    {
        if (empty($this->translationMap)) {
            $this->loadTranslationMap();
        }
        return $this->translationMap;
    }

    private function loadTranslationMap()
    {
        global $tsm;
        $translations = $tsm->getPolylangManager()->getAllTranslationsPost($this->post["ID"]);
        $_translationMap = [];
        foreach ($translations as $slug => $data) {
            $translationId = $data["postId"];
            $translation = $translationId ? get_post($translationId, ARRAY_A) : null;
            $isTranslated = !empty($translation);
            $translationIsUpdated = $translation && $translation["post_modified"] > $this->post["post_modified"];

            $_translationMap[$slug] = [
                "translation" => $isTranslated ? [
                    "ID" => $translation["ID"],
                    "title" => $translation["post_title"],
                ] : null,
                "translationIsUpdated" => $translationIsUpdated,
            ];
        }
        $this->translationMap = $_translationMap;
    }
}

$GetActionsByPost = new GetActionsByPost();
$GetActionsByPost->init();