<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Objects;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;

if (!defined("ABSPATH")) {
    exit;
}

class GetObjectsType
{
    public function __construct()
    {
    }

    public function init()
    {
        add_action("wp_ajax_traduire-sans-migraine_get_objects_type", [$this, "getObjectsType"]);
    }

    public function getObjectsType()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        $allowedObjectTypes = [
            DAOActions::$ACTION_TYPE["TERMS"],
            DAOActions::$ACTION_TYPE["POST_PAGE"],
        ];
        if ($tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])) {
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["EMAIL"];
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["PRODUCT"];
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["ATTRIBUTES"];
        }
        if (is_plugin_active("elementor/elementor.php")) {
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"];
        }

        wp_send_json_success([
            "objectsType" => $allowedObjectTypes,
        ]);
        wp_die();
    }
}

$GetObjectsType = new GetObjectsType();
$GetObjectsType->init();