<?php

namespace TraduireSansMigraine\Wordpress\RestAPI;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TranslateHelper;

if (!defined("ABSPATH")) {
    exit;
}

class RestAPI
{

    public function __construct() {
    }

    public function loadHooks() {
        add_action( 'rest_api_init', [Ping::getInstance(), "registerEndpoints"]);
        add_action( 'rest_api_init', [Translations::getInstance(), "registerEndpoints"]);
    }

    public static function init() {
        $instance = self::getInstance();
        $instance->loadHooks();
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}