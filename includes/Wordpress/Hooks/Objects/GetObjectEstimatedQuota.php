<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Objects;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;

if (!defined("ABSPATH")) {
    exit;
}

class GetObjectEstimatedQuota
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
        add_action("wp_ajax_traduire-sans-migraine_get_object_estimated_quota", [$this, "getObjectEstimatedQuota"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getObjectEstimatedQuota()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["objectId"]) || !isset($_GET["objectType"]) || !in_array($_GET["objectType"], DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $action = AbstractAction::getInstance([
            "objectId" => $_GET["objectId"],
            "slugTo" => null,
            "origin" => "HOOK",
            "actionType" => $_GET["objectType"],
        ]);
        wp_send_json_success([
            "estimatedQuota" => $action->getEstimatedQuota()
        ]);
        wp_die();
    }

}

$GetObjectEstimatedQuota = new GetObjectEstimatedQuota();
$GetObjectEstimatedQuota->init();