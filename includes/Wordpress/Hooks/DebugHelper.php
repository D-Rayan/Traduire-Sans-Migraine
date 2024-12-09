<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

if (!defined("ABSPATH")) {
    exit;
}

class DebugHelper
{

    public function __construct() {}

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
        wp_die();
    }
}

$DebugHelper = new DebugHelper();
$DebugHelper->init();
