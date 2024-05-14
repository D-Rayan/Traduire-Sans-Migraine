<?php

namespace TraduireSansMigraine;

use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class Settings
{
    private $settings;

    public function __construct() {
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
            add_action( 'admin_notices', [$this, "noticePhp"] );
        }

        return $phpIsValid;
    }

    public function noticePhp() {
        Alert::render(TextDomain::__("PHP version is too low"), TextDomain::__("%s required at least PHP %s",  TSM__NAME, TSM__PHP_REQUIREMENT), "error");
    }

    private function checkPlugins($printNotice = false)
    {
        $pluginsLists = [
            [
                "Polylang" => function_exists("pll_the_languages") || defined( 'POLYLANG_VERSION' ),
            ]
        ];

        $result = true;
        $pluginsListsMissing = [];
        foreach ($pluginsLists as $plugins) {
            $resultList = false;
            $listsMissing = [];
            foreach ($plugins as $pluginName => $pluginIsAvailable) {
                if (!$pluginIsAvailable) {
                    $listsMissing[] = $pluginName;
                }
                $resultList = $resultList || $pluginIsAvailable;
            }
            $result = $result && $resultList;
            if (!$resultList) {
                $pluginsListsMissing[] = $listsMissing;
            }
        }

        if (!$result && $printNotice) {
            foreach ($pluginsListsMissing as $listMissing) {
                $notice = TextDomain::__("%s required at least one of the following plugins %s", TSM__NAME, join(", ", $listMissing));
                add_action( 'admin_notices', function () use ($notice) {
                    Alert::render(TextDomain::__("Missing required plugins"), $notice, "error");
                });
            }
        }

        return $result;
    }

    public function generateAndSaveToken(): string {
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

    public function getToken(): string {
        $token = get_option("seo_sans_migraine_token");
        if (empty($token)) {
            $token = $this->generateAndSaveToken();
        }

        return $token;
    }

    public function saveSettings($settings) {
        update_option("seo_sans_migraine_settings", $settings);
        $this->settings = $settings;
    }

    public function getSettings() {
        return $this->settings;
    }

    public function settingIsEnabled($name) {
        return !isset($this->settings[$name]) || $this->settings[$name] == true;
    }
}