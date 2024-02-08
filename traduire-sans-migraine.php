<?php

/**
 * Plugin Name: Traduire Sans Migraine
 * Plugin URI: https://www.seo-sans-migraine.fr/traduire-sans-migraine
 * Description: Traduire Sans Migraine va vous aider à traduire votre contenu tout en gardant les bonnes pratiques SEO.
 * Version: 0.0.6
 * Author: Seo Sans Migraine
 * Author URI: https://www.seo-sans-migraine.fr
 * License: GPL2
 */


use TraduireSansMigraine\SeoSansMigraine\Hooks;

if (!defined("ABSPATH")) {
    exit;
}

include "env.php";

define("TSM__PHP_REQUIREMENT", "7.0");
define("TSM__VERSION", "0.0.6");
define("TSM__WORDPRESS_REQUIREMENT", "5.8");

define("TSM__ABSOLUTE_PATH", __DIR__);
define("TSM__RELATIVE_PATH", plugin_dir_url(__FILE__));
define("TSM__PLUGIN_BASENAME", plugin_basename( __FILE__ ));
define("TSM__NAME", "Traduire Sans Migraine");
define("TSM__SLUG", "traduire-sans-migraine");
define("TSM__TEXT_DOMAIN", "traduire-sans-migraine");

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
        $this->updater->init();
        $this->loadTextDomain();
        $this->loadComponents();
        $this->loadPages();
        add_action("admin_init", [$this, "checkRequirements"]);
        $hooks = new Hooks();
        $hooks->init();
    }
}

$tsm = new TraduireSansMigraine();