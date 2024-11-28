<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

if (!defined("ABSPATH")) {
    exit;
}

class DebugHelper
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
        add_action("wp_ajax_traduire-sans-migraine_send_debug", [$this, "debugTranslation"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function debugTranslation()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["objectId"]) || !isset($_POST["objectType"]) || !isset($_POST["code"]) || !in_array($_POST["objectType"], DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $objectId = $_POST["objectId"];
        $objectType = $_POST["objectType"];
        // @todo : implement
        if ($objectType === DAOActions::$ACTION_TYPE["POST_PAGE"]) {
            $code = $_POST["code"];
            $originalPost = get_post($objectId);
            $postMetas = get_post_meta($originalPost->ID);

            $result = $tsm->getClient()->checkCredential();
            if (!$result) {
                wp_send_json_error(seoSansMigraine_returnLoginError(), 400);
                wp_die();
            }
            $language = LanguagePost::getLanguage($originalPost->ID);
            if (!$language) {
                wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
                wp_die();
            }
            $pluginsActives = get_option("active_plugins");
            $response = $tsm->getClient()->sendDebugData([
                "post" => $originalPost,
                "postMetas" => $postMetas,
                "codeFrom" => $language["code"],
                "pluginsActives" => $pluginsActives,
                "code" => $code
            ]);
            if ($response["success"]) {
                wp_send_json_success([]);
            } else {
                wp_send_json_error([
                    "message" => 'check_debug_code'
                ], 400);
            }
        }

        wp_die();
    }
}

$DebugHelper = new DebugHelper();
$DebugHelper->init();