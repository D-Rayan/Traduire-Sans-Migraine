<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Languages;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;

if (!defined("ABSPATH")) {
    exit;
}

class SetLanguage
{

    public function __construct()
    {
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
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
        add_action("wp_ajax_traduire-sans-migraine_set_language", [$this, "setLanguage"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function setLanguage()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["objectId"]) || !isset($_POST["objectType"]) || !isset($_POST["slug"]) || !in_array($_POST["objectType"], DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $slug = $_POST["slug"];
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$slug])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        $objectId = $_POST["objectId"];
        $objectType = $_POST["objectType"];
        $language = $languages[$slug];
        switch ($objectType) {
            case DAOActions::$ACTION_TYPE["POST_PAGE"]:
            case DAOActions::$ACTION_TYPE["PRODUCT"]:
            case DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]:
                $postType = get_post_type($objectId);
                $allowedPostTypes = apply_filters("tsm-post-type-translatable", ["post", "page", "elementor_library"]);
                if (!in_array($postType, $allowedPostTypes)) {
                    wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
                    wp_die();
                }
                LanguagePost::setLanguage($objectId, $language);
                break;
            case DAOActions::$ACTION_TYPE["TERMS"]:
                LanguageTerm::setLanguage($objectId, $language);
                break;
            default:
                wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
                wp_die();
        }
        wp_send_json_success([]);
    }
}

$SetLanguage = new SetLanguage();
$SetLanguage->init();