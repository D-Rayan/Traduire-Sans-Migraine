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

use TraduireSansMigraine\Front\EditorPage;
use TraduireSansMigraine\Front\NotificationsPage;
use TraduireSansMigraine\Front\PluginsPage;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Hooks\Hooks;
use TraduireSansMigraine\Wordpress\Menu;
use TraduireSansMigraine\Wordpress\OfflineProcess;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\Requirements;
use TraduireSansMigraine\Wordpress\RestAPI\RestAPI;
use TraduireSansMigraine\Wordpress\TextDomain;
use TraduireSansMigraine\Wordpress\Updater;

if (!defined("ABSPATH")) {
    exit;
}
include "env.php";
define("TSM__ABSOLUTE_PATH", __DIR__);
define("TSM__RELATIVE_PATH", plugin_dir_url(__FILE__));
define("TSM__PLUGIN_BASENAME", plugin_basename(__FILE__));
define("TSM__ASSETS_PATH", TSM__RELATIVE_PATH . "/front/assets/");
define("TSM__PLUGIN_NAME", "traduire-sans-migraine");
require_once TSM__ABSOLUTE_PATH . "/autoload.php";

class TraduireSansMigraine
{
    private $settings;
    private $client;
    private $linkManager;
    private $polylangManager;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->client = new Client();
        $this->polylangManager = new Languages\PolylangManager();
        $this->linkManager = new Wordpress\LinkManager();
        $this->init();
    }

    public function init()
    {
        TextDomain::init();
        if (Requirements::getInstance()->handleRequirements() === false) {
            return;
        }
        register_activation_hook(__FILE__, [$this, "setPluginAsEnabled"]);
        register_deactivation_hook(__FILE__, [$this, "removeAllSettings"]);
        if (isset($_GET["tsm"]) && $_GET["tsm"] === "polylang_installed") {
            $this->setPluginAsEnabled();
        }
        if ($this->isPluginGotEnabled()) {
            add_action("admin_init", [$this, "displayMessageEnabled"]);
        }
        Updater::init();
        $this->handleJSON();
        DAOActions::init();
        OfflineProcess::init();
        Queue::init();
        Menu::init();
        EditorPage::init();
        PluginsPage::init();
        NotificationsPage::init();
        RestAPI::init();
        Hooks::init();
    }

    public function setPluginAsEnabled()
    {
        global $wpdb;

        update_option('tsm-has-been-activated', true, false);
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%_seo_sans_migraine_state%'");
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%_seo_sans_migraine_post%'");
    }

    private function isPluginGotEnabled()
    {
        return get_option('tsm-has-been-activated', false);
    }

    private function handleJSON()
    {
        if (empty($_POST)) {
            $file_content_input = file_get_contents('php://input');
            if (!empty($file_content_input)) {
                $_POST = json_decode($file_content_input, true);
            }
        }
    }

    public function displayMessageEnabled()
    {
        delete_option('tsm-has-been-activated');
        add_action("admin_notices", function () {
            render_seoSansMigraine_alert("Traduire Sans Migraine est activé !", sprintf("Tu ne sais pas par où commencer ? Aucun soucis il te suffit de cliquer <a href='%s'>ici</a> et de suivre les étapes", admin_url("admin.php?page=traduire-sans-migraine")), "success");
        });
    }

    public function removeAllSettings()
    {
        global $wpdb;
        if (!isset($_GET["delete_configuration"])) {
            return;
        }
        $wpdb->query("DELETE FROM {$wpdb->prefix}options WHERE option_name LIKE '%seo_sans_migraine%'");
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function getClient()
    {
        return $this->client;
    }

    public function getPolylangManager()
    {
        return $this->polylangManager;
    }

    public function getLinkManager()
    {
        return $this->linkManager;
    }
}

$tsm = new TraduireSansMigraine();
global $tsm;