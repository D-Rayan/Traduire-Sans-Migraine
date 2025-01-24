<?php

namespace TraduireSansMigraine\Wordpress\Filters;

if (!defined("ABSPATH")) {
    exit;
}

class Filters
{

    public function __construct()
    {
    }

    public static function init()
    {
        $instance = self::getInstance();
        $instance->loadFilters();
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function loadFilters()
    {
        require_once __DIR__ . "/../../Wordpress/Filters/EnrichAction.php";
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