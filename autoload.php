<?php

require_once TSM__ABSOLUTE_PATH . "/includes/functions.php";
require_once TSM__ABSOLUTE_PATH . "/includes/Settings.php";
// Will autoload all Components
spl_autoload_register(function ($class) {
    $parameters = [
        ["prefix" => "TraduireSansMigraine\\SeoSansMigraine\\", "base_dir" => __DIR__ . "/includes/SeoSansMigraine/"],
        ["prefix" => "TraduireSansMigraine\\Languages\\", "base_dir" => __DIR__ . "/includes/Languages/"],
        ["prefix" => "TraduireSansMigraine\\Wordpress\\", "base_dir" => __DIR__ . "/includes/Wordpress/"],
        ["prefix" => "TraduireSansMigraine\\Front\\", "base_dir" => __DIR__ . "/front/"],
    ];

    foreach ($parameters as $parameter) {
        $prefix = $parameter["prefix"];
        $base_dir = $parameter["base_dir"];

        $len = strlen($prefix);
        if (strncmp($prefix, $class, $len) !== 0) {
            continue;
        }

        $relative_class = substr($class, $len);
        $className = str_replace('\\', '/', $relative_class);
        $file = $base_dir . $className . '.php';

        if (file_exists($file)) {
            require_once $file;
        }
    }
});