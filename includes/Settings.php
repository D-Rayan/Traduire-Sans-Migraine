<?php

namespace TraduireSansMigraine;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class Settings
{
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
        Step::render(TextDomain::__("PHP version is too low"), TextDomain::__("%s required at least PHP %s",  TSM__NAME, TSM__PHP_REQUIREMENT));
    }

    private function checkPlugins($printNotice = false)
    {
        $pluginsLists = [
            [
                "Polylang" => function_exists("pll_the_languages"),
                "WPML" => function_exists("wpml_current_language"),
                "MultilingualPress" => function_exists("mlp_get_interlinked_permalinks"),
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
                    Step::render(TextDomain::__("Missing required plugins"), $notice, "error");
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
}