<?php

/**
 * Plugin Name: PLACEHOLDER_NAME
 * Plugin URI: https://www.seo-sans-migraine.com/translate-without-headache
 * Description: Translate Without Headache will help you translate your content within keeping good SEO practices.
 * Version: PLACEHOLDER_VERSION
 * Author: Otter Corp
 * Author URI: https://www.seo-sans-migraine.com
 * Text Domain: traduire-sans-migraine
 * Domain Path: /languages
 * License: GPL2
 */

namespace TraduireSansMigraine;


use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Wordpress\Hooks\DebugHelper;
use TraduireSansMigraine\Wordpress\Hooks\GetPostNotifications;
use TraduireSansMigraine\Wordpress\Hooks\GetPostTranslated;
use TraduireSansMigraine\Wordpress\Hooks\PrepareTranslation;
use TraduireSansMigraine\Wordpress\Hooks\RestAPI;
use TraduireSansMigraine\Wordpress\Hooks\SendReasonsDeactivate;
use TraduireSansMigraine\Wordpress\Hooks\StartTranslation;
use TraduireSansMigraine\Wordpress\Hooks\TranslateInternalLinks;
use TraduireSansMigraine\Wordpress\Hooks\TranslationState;
use TraduireSansMigraine\Wordpress\Menu;
use TraduireSansMigraine\Wordpress\OfflineProcess;
use TraduireSansMigraine\Wordpress\PostsSearch;
use TraduireSansMigraine\Wordpress\TextDomain;
use TraduireSansMigraine\Wordpress\Updater;

if (!defined("ABSPATH")) {
    exit;
}
include "env.php";
define("TSM__ABSOLUTE_PATH", __DIR__);
define("TSM__RELATIVE_PATH", plugin_dir_url(__FILE__));
define("TSM__PLUGIN_BASENAME", plugin_basename( __FILE__ ));
define("TSM__ASSETS_PATH", TSM__RELATIVE_PATH . "/front/assets/");
define("TSM__PLUGIN_NAME", "traduire-sans-migraine");
require_once TSM__ABSOLUTE_PATH . "/includes/autoload.php";
class TraduireSansMigraine {

    private $settings;
    private $updater;
    private $textDomain;


    private $menu;
    public function __construct() {
        $this->settings = new Settings();
        $this->updater = new Updater();
        $this->textDomain = new TextDomain();
        $this->menu = new Menu();
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
        if (empty($_POST)) {
            $file_content_input = file_get_contents('php://input');
            if (!empty($file_content_input)) {
                $_POST = json_decode($file_content_input, true);
            }
        }
        $this->updater->init();
        $this->textDomain->loadTextDomain();
        $this->loadComponents();
        $this->loadPages();
        register_activation_hook(__FILE__, [$this, "prepareActivationPlugin"]);
        register_deactivation_hook( __FILE__, [$this, "deactivateTsm"] );
        add_action("admin_init", [$this, "checkRequirements"]);
        add_action("admin_init", [$this, "messageSuccessActivation"]);
        OfflineProcess::getInstance()->init();
        $this->menu->init();
        DebugHelper::getInstance()->init();
        GetPostTranslated::getInstance()->init();
        PrepareTranslation::getInstance()->init();
        RestAPI::getInstance()->init();
        StartTranslation::getInstance()->init();
        TranslationState::getInstance()->init();
        PostsSearch::getInstance()->init();
        TranslateInternalLinks::getInstance()->init();
        GetPostNotifications::getInstance()->init();
        SendReasonsDeactivate::getInstance()->init();
    }

    public function prepareActivationPlugin() {
        add_option('tsm-has-been-activated', true);
    }

    public function messageSuccessActivation() {
        if (get_option('tsm-has-been-activated', false)) {
            delete_option('tsm-has-been-activated');
            add_action("admin_notices", function () {
                Alert::render(
                    TextDomain::__("Traduire Sans Migraine is enabled"),
                    TextDomain::__("You don't know where to start? Don't worry just click <a href='%s'>here</a> and follow the steps", admin_url("admin.php?page=traduire-sans-migraine")),
                    "info"
                );
            });
        }
    }

    public function deactivateTsm() {
        global $wpdb;

        if (isset($_GET["delete_configuration"])) {
            $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%seo_sans_migraine%'");
        }
    }
}

$tsm = new TraduireSansMigraine();