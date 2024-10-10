<?php

namespace TraduireSansMigraine;

if (!defined("ABSPATH")) {
    exit;
}

class Settings
{
    static public $KEYS = [
        "yoastSEO" => "yoastSEO",
        "rankMath" => "rankMath",
        "SEOPress" => "SEOPress",
        "translateAssets" => "translateAssets",
        "translateCategories" => "translateCategories",
        "autoTranslateLinks" => "autoTranslateLinks",
        "autoDeletionTranslations" => "autoDeletionTranslations"
    ];
    private $settings;
    private $token;

    public function __construct()
    {
        $this->token = null;
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
        if (empty($this->settings)) {
            $this->loadSettings();
        }
        return $this->settings;
    }

    private function loadSettings()
    {
        global $tsm;

        if (!function_exists("is_plugin_active")) {
            include_once(ABSPATH . "wp-admin/includes/plugin.php");
        }

        $account = $tsm->getClient()->getAccount();
        $this->settings = [
            self::$KEYS["yoastSEO"] => [
                "enabled" => true,
                "available" => (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")),
            ],
            self::$KEYS["rankMath"] => [
                "enabled" => true,
                "available" => (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math"))
            ],
            self::$KEYS["SEOPress"] => [
                "enabled" => true,
                "available" => (is_plugin_active("wp-seopress/seopress.php"))
            ],
            self::$KEYS["translateAssets"] => [
                "enabled" => true,
                "available" => true
            ],
            self::$KEYS["translateCategories"] => [
                "enabled" => true,
                "available" => true
            ],
            self::$KEYS["autoTranslateLinks"] => [
                "enabled" => false,
                "available" => isset($account["canUseBackgroundLinksTranslation"]) && $account["canUseBackgroundLinksTranslation"]
            ],
            self::$KEYS["autoDeletionTranslations"] => [
                "enabled" => false,
                "available" => true
            ],
        ];
        $settings = get_option("seo_sans_migraine_settings");
        if (!empty($settings)) {
            foreach ($settings as $key => $enabled) {
                if (isset($this->settings[$key])) {
                    $this->settings[$key]["enabled"] = $enabled == true && $this->settings[$key]["available"] == true;
                    continue;
                }
                $oldKeys = [
                    "rank_math_description" => self::$KEYS["rankMath"],
                    "rank_math_title" => self::$KEYS["rankMath"],
                    "rank_math_focus_keyword" => self::$KEYS["rankMath"],
                    "seopress_titles_desc" => self::$KEYS["SEOPress"],
                    "seopress_titles_title" => self::$KEYS["SEOPress"],
                    "seopress_analysis_target_kw" => self::$KEYS["SEOPress"],
                    "_yoast_wpseo_title" => self::$KEYS["yoastSEO"],
                    "_yoast_wpseo_metadesc" => self::$KEYS["yoastSEO"],
                    "_yoast_wpseo_metakeywords" => self::$KEYS["yoastSEO"],
                    "yoast_wpseo_focuskw" => self::$KEYS["yoastSEO"],
                ];
                if (isset($oldKeys[$key])) {
                    $relatedKey = $oldKeys[$key];
                    $this->settings[$relatedKey]["enabled"] = $enabled == true && $this->settings[$relatedKey]["enabled"] == true && $this->settings[$relatedKey]["available"] == true;
                }
            }
        }
    }

    public function settingIsEnabled($name)
    {
        if (empty($this->settings)) {
            $this->loadSettings();
        }
        return isset($this->settings[$name]) && $this->settings[$name]["enabled"] == true && $this->settings[$name]["available"] == true;
    }
}