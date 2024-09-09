<?php

namespace TraduireSansMigraine;

if (!defined("ABSPATH")) {
    exit;
}

class Settings
{
    private $settings;
    private $token;

    public function __construct()
    {
        $this->token = null;
    }

    private function loadSettings()
    {
        $this->settings = [
            "yoastSEO" => [
                "enabled" => true,
                "available" => (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")),
            ],
            "rankMath" => [
                "enabled" => true,
                "available" => (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math"))
            ],
            "SEOPress" => [
                "enabled" => true,
                "available" => (is_plugin_active("wp-seopress/seopress.php"))
            ],
            "translateAssets" => [
                "enabled" => true,
                "available" => true
            ],
            "translateCategories" => [
                "enabled" => true,
                "available" => true
            ],
        ];
        $settings = get_option("seo_sans_migraine_settings");
        if (!empty($settings)) {
            foreach ($settings as $key => $enabled) {
                if (isset($this->settings[$key])) {
                    $this->settings[$key]["enabled"] = $enabled == true;
                    continue;
                }
                $oldKeys = [
                    "rank_math_description" => "rankMath",
                    "rank_math_title" => "rankMath",
                    "rank_math_focus_keyword" => "rankMath",
                    "seopress_titles_desc" => "SEOPress",
                    "seopress_titles_title" => "SEOPress",
                    "seopress_analysis_target_kw" => "SEOPress",
                    "_yoast_wpseo_title" => "yoastSEO",
                    "_yoast_wpseo_metadesc" => "yoastSEO",
                    "_yoast_wpseo_metakeywords" => "yoastSEO",
                    "yoast_wpseo_focuskw" => "yoastSEO",
                ];
                if (isset($oldKeys[$key])) {
                    $this->settings[$oldKeys[$key]]["enabled"] = $enabled == true && $this->settings[$oldKeys[$key]]["enabled"] == true;
                }
            }
        }
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
        if (!empty($this->token)) {
            return $this->token;
        }
        $token = get_option("seo_sans_migraine_token");
        if (empty($token)) {
            $token = $this->generateAndSaveToken();
        }
        $this->token = $token;

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
    }

    public function getSettings()
    {
        if (empty($this->settings)) { $this->loadSettings(); }
        return $this->settings;
    }

    public function settingIsEnabled($name)
    {
        if (empty($this->settings)) { $this->loadSettings(); }
        return !isset($this->settings[$name]) || $this->settings[$name] == true;
    }
}