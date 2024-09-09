<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use RuntimeException;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TranslateHelper;

if (!defined("ABSPATH")) {
    exit;
}

class Hooks
{

    public function __construct() {
    }

    public function loadHooks() {
        // maybe load just the hooks that will be used thanks to action variable
        $files = glob(__DIR__ . '/*.php');
        if ($files === false) {
            throw new RuntimeException("Failed to glob for function files");
        }
        foreach ($files as $file) {
            if ($file === __FILE__) {
                continue;
            }
            require_once $file;
        }
        unset($file);
        unset($files);
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