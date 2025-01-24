<?php

namespace TraduireSansMigraine\Woocommerce;

use RuntimeException;
use TraduireSansMigraine\Front\Blocks;
use TraduireSansMigraine\Settings;

if (!defined("ABSPATH")) {
    exit;
}

class Woocommerce
{

    public function __construct()
    {
    }

    public static function init()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"], true)) {
            return;
        }
        $instance = self::getInstance();
        $instance->loadBlocks();
        $instance->loadHooks();
        $instance->addFilters();
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    private function loadBlocks()
    {
        Blocks::init();
    }

    public function loadHooks()
    {
        // maybe load just the hooks that will be used thanks to action variable
        $files = $this->rglob(__DIR__ . '/*.php');
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

    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge(
                [], $files, $this->rglob($dir . "/" . basename($pattern), $flags)
            );
        }
        return $files;
    }

    public function addFilters()
    {
        add_filter("tsm-post-type-translatable", [$this, "addWooCommercePostType"]);
    }

    public function addWooCommercePostType($postTypes)
    {
        $postTypes[] = "product";
        return $postTypes;
    }
}

