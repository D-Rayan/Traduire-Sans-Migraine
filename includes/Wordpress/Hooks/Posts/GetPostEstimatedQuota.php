<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Wordpress\Object\Action;

if (!defined("ABSPATH")) {
    exit;
}

class GetPostEstimatedQuota
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
        add_action("wp_ajax_traduire-sans-migraine_get_post_estimated_quota", [$this, "getPostEstimatedQuota"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getPostEstimatedQuota()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["postId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $action = new Action([
            "postId" => $_GET["postId"],
            "slugTo" => null,
            "origin" => "HOOK"
        ]);
        wp_send_json_success([
            "estimatedQuota" => $action->getEstimatedQuota()
        ]);
        wp_die();
    }

}

$GetPostEstimatedQuota = new GetPostEstimatedQuota();
$GetPostEstimatedQuota->init();