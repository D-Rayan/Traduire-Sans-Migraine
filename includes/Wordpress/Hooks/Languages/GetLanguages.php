<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Languages;

if (!defined("ABSPATH")) {
    exit;
}

class GetLanguages
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
        add_action("wp_ajax_traduire-sans-migraine_get_languages", [$this, "getLanguages"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getLanguages()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        $polylangManager = $tsm->getPolylangManager();
        $languages = $polylangManager->getLanguages();
        wp_send_json_success([
            "languages" => $languages
        ]);
        wp_die();
    }
}

$GetLanguages = new GetLanguages();
$GetLanguages->init();