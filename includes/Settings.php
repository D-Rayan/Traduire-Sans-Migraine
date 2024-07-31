<?php

namespace TraduireSansMigraine;

use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class Settings
{
    private $settings;

    public function __construct()
    {
        $this->settings = get_option("seo_sans_migraine_settings");
        if (!$this->settings) {
            $this->settings = [];
        }
    }

    public function checkRequirements()
    {
        return $this->checkPhp(true) && $this->checkPlugins(true);
    }

    private function checkPhp($printNotice = false)
    {
        $requiredMinimumPhpVersion = TSM__PHP_REQUIREMENT;
        $phpIsValid = version_compare(PHP_VERSION, $requiredMinimumPhpVersion, ">=");
        if (!$phpIsValid && $printNotice) {
            add_action('admin_notices', [$this, "noticePhp"]);
        }

        return $phpIsValid;
    }

    public function noticePhp()
    {
        Alert::render(TextDomain::__("PHP version is too low"), TextDomain::__("%s required at least PHP %s", TSM__NAME, TSM__PHP_REQUIREMENT), "error");
    }

    private function checkPlugins($printNotice = false)
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        if (!function_exists("pll_the_languages") && !defined('POLYLANG_VERSION')) {
            foreach ($plugins as $path => $plugin) {
                if (strpos($plugin["Name"], "Polylang") !== false) {
                    activate_plugin($path);

                    if ($printNotice) {
                        add_action('admin_notices', function () {
                            Alert::render(
                                TextDomain::__("Missing required plugin"),
                                TextDomain::__("Polylang is a dependence for %s so it has been activate automatically", TSM__NAME),
                                "success"
                            );
                        });
                    }

                    break;
                }
            }
        }

        if (!function_exists("pll_the_languages") && !defined('POLYLANG_VERSION')) {
            if ($printNotice) {
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
            }

            return false;
        }

        return true;
    }

    public function generateAndSaveToken(): string
    {
        $characters = 'ABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789';
        $token = '';

        for ($i = 0; $i < 4; $i++) {
            for ($j = 0; $j < 4; $j++) {
                $token .= $characters[rand(0, strlen($characters) - 1)];
            }
            if ($i < 3) {
                $token .= '-';
            }
        }

        add_option("seo_sans_migraine_token", $token);
        return $token;
    }

    public function getToken(): string
    {
        $token = get_option("seo_sans_migraine_token");
        if (empty($token)) {
            $token = $this->generateAndSaveToken();
        }

        return $token;
    }

    public function deleteToken()
    {
        delete_option("seo_sans_migraine_token");
    }

    public function deleteSettings()
    {
        delete_option("seo_sans_migraine_settings");
    }

    public function saveSettings($settings)
    {
        update_option("seo_sans_migraine_settings", $settings);
        $this->settings = $settings;
    }

    public function getSettings()
    {
        return $this->settings;
    }

    public function settingIsEnabled($name)
    {
        return !isset($this->settings[$name]) || $this->settings[$name] == true;
    }
}