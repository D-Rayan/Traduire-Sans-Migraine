<?php

/**
 * Plugin Name: Traduire Sans Migraine
 * Plugin URI: https://www.seo-sans-migraine.fr/traduire-sans-migraine
 * Description: Traduire Sans Migraine va vous aider à traduire votre contenu tout en gardant les bonnes pratiques SEO.
 * Version: 0.2.0
 * Author: Seo Sans Migraine
 * Author URI: https://www.seo-sans-migraine.fr
 * Text Domain: traduire-sans-migraine
 * License: GPL2
 */

namespace TraduireSansMigraine;


use TraduireSansMigraine\SeoSansMigraine\Hooks;
use TraduireSansMigraine\Wordpress\TextDomain;
use TraduireSansMigraine\Wordpress\Updater;

if (!defined("ABSPATH")) {
    exit;
}

include "env.php";
define("TSM__ABSOLUTE_PATH", __DIR__);
define("TSM__RELATIVE_PATH", plugin_dir_url(__FILE__));
define("TSM__PLUGIN_BASENAME", plugin_basename( __FILE__ ));
require_once TSM__ABSOLUTE_PATH . "/includes/autoload.php";
class TraduireSansMigraine {

    private $settings;
    private $updater;

    private $textDomain;
    public function __construct() {
        $this->settings = new Settings();
        $this->updater = new Updater();
        $this->textDomain = new TextDomain();
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

    public function init() {
        $this->updater->init();
        $this->textDomain->loadTextDomain();
        $this->loadComponents();
        $this->loadPages();
        add_action("admin_init", [$this, "checkRequirements"]);
        $hooks = new Hooks();
        $hooks->init();
    }
}

$tsm = new TraduireSansMigraine();