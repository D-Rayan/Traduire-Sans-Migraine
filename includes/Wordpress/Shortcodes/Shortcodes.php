<?php

namespace TraduireSansMigraine\Wordpress\Shortcodes;

use RuntimeException;

if (!defined("ABSPATH")) {
    exit;
}

class Shortcodes
{

    public function __construct()
    {
    }

    public static function init()
    {
        $instance = self::getInstance();
        $instance->loadShortcodes();
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function loadShortcodes()
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
}