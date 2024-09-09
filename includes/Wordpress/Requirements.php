<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;

if (!defined("ABSPATH")) {
    exit;
}

class Requirements
{
    public function handleRequirements() {
        if (!$this->checkPhpVersion()) {
            add_action("admin_notices", [$this, "noticePhp"]);
            return false;
        }
        if (!$this->havePolylang()) {
            if ($this->activatePolylangIfAvailable()) {
                add_action('admin_notices', function () {
                    Alert::render(
                        TextDomain::__("Missing required plugin"),
                        TextDomain::__("Polylang is a dependence for %s so it has been activate automatically", TSM__NAME),
                        "success"
                    );
                });
                return false;
            }
            add_action('admin_notices', function () {
                Alert::render(
                    TextDomain::__("Missing required plugin"),
                    TextDomain::__("%s required Polylang to be installed and active", TSM__NAME)
                    . " " . Button::getHTML(
                        "Install",
                        "primary",
                        "install-required-plugins", [
                        "wpNonce" => wp_create_nonce("traduire-sans-migraine_install_required_plugin"),
                    ]),
                    "error"
                );
            });
            add_action("wp_ajax_traduire-sans-migraine_install_required_plugin", [$this, "installRequiredPlugins"]);
            return false;
        }
        return true;
    }

    public function installRequiredPlugins() {
        if (!current_user_can('install_plugins')) {
            wp_die();
        }
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine_install_required_plugin")) {
            wp_die();
        }
        include_once( ABSPATH . 'wp-admin/includes/plugin-install.php' );
        include_once( ABSPATH . 'wp-admin/includes/class-wp-upgrader.php' );
        $api = plugins_api( 'plugin_information', array( 'slug' => 'polylang' ) );
        $upgrader = new \Plugin_Upgrader();
        $install = $upgrader->install($api->download_link);
        if (is_wp_error($install)) {
            wp_die();
        }
    }

    public function checkPhpVersion() {
        $requiredMinimumPhpVersion = TSM__PHP_REQUIREMENT;
        $phpIsValid = version_compare(PHP_VERSION, $requiredMinimumPhpVersion, ">=");

        return $phpIsValid;
    }

    public function noticePhp() {
        Alert::render(TextDomain::__("PHP version is too low"), TextDomain::__("%s required at least PHP %s", TSM__NAME, TSM__PHP_REQUIREMENT), "error");
    }

    public function activatePolylangIfAvailable() {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        foreach ($plugins as $path => $plugin) {
            if (strpos($plugin["Name"], "Polylang") !== false) {
                activate_plugin($path);
                return true;
            }
        }
        return false;
    }

    public function havePolylang() {
        return function_exists("pll_the_languages") || defined('POLYLANG_VERSION');
    }

    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }
}