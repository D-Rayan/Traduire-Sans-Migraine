<?php

include __DIR__ . "/Main/Main.php";
include __DIR__ . "/Alert/Alert.php";
include __DIR__ . "/Modal/Modal.php";
include __DIR__ . "/Checkbox/Checkbox.php";
include __DIR__ . "/Step/Step.php";
include __DIR__ . "/Button/Button.php";
include __DIR__ . "/Suggestions/Suggestions.php";
include __DIR__ . "/Tooltip/Tooltip.php";


/*
 *
// Will autoload all Components
spl_autoload_register(function ($class) {
    $prefix = 'TraduireSansMigraine\\Front\\Components\\';
    $base_dir = __DIR__ . '/';

    $len = strlen($prefix);
    if (strncmp($prefix, $class, $len) !== 0) {
        return;
    }

    $relative_class = substr($class, $len);
    $className = str_replace('\\', '/', $relative_class);
    $file = $base_dir . $className . "/" . $className . '.php';

    if (file_exists($file)) {
        require_once $file;
    }
});
 */