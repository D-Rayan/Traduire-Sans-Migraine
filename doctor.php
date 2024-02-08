<?php

/**
 * Plugin Name: TraduireSansMigraine
 * Plugin URI: https://www.seo-sans-migraine.fr/traduire-sans-migraine
 * Description: TraduireSansMigraine is a plugin to help you improve your multilingual SEO.
 * Version: 0.0.1
 * Author: Seo Sans Migraine
 * Author URI: https://www.seo-sans-migraine.fr
 * License: GPL2
 */


use TraduireSansMigraine\SeoSansMigraine\Hooks;

if (!defined("ABSPATH")) {
    exit;
}
ini_set( 'display_errors', 1 );
define("TSM__PHP_REQUIREMENT", "7.0");
define("TSM__VERSION", "0.0.6");
define("TSM__WORDPRESS_REQUIREMENT", "5.8");
define("TSM__NAME", "TraduireSansMigraine");
define("TSM__SLUG", "traduire-sans-migraine");
define("TSM__TEXT_DOMAIN", "traduire-sans-migraine");
define("TSM__ABSOLUTE_PATH", __DIR__);
define("TSM__RELATIVE_PATH", plugin_dir_url(__FILE__));
define("TSM__URL_DOMAIN", "https://www.seo-sans-migraine.fr");
define("TSM__API_DOMAIN", "https://traduire-sans-migraine.seo-sans-migraine.fr/api");

require_once TSM__ABSOLUTE_PATH . "/includes/autoload.php";
class TraduireSansMigraine {

    private $settings;
    private $updater;
    public function __construct() {
        $this->settings = new TraduireSansMigraine\Settings();
        $this->updater = new TraduireSansMigraine\Updater();
        $this->init();
    }

    public function checkRequirements() {
        if (!$this->settings->checkRequirements()) {
            deactivate_plugins( plugin_basename( __FILE__ ) );
            if ( isset( $_GET['activate'] ) ) {
                unset( $_GET['activate'] );
            }
        }
    }

    public function loadComponents() {
        require_once TSM__ABSOLUTE_PATH . "/front/Components/autoload.php";
    }

    public function loadPages() {
        require_once TSM__ABSOLUTE_PATH . "/front/Pages/autoload.php";
    }

    public function loadTextDomain() {
        load_plugin_textdomain(TSM__TEXT_DOMAIN, false, TSM__RELATIVE_PATH . "/languages");
    }

    public function init() {
        $this->loadTextDomain();
        $this->loadComponents();
        $this->loadPages();
        add_action("admin_init", [$this, "checkRequirements"]);
        $hooks = new Hooks();
        $hooks->init();
    }
}

$tsm = new TraduireSansMigraine();