<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Settings;

if (!defined("ABSPATH")) {
    exit;
}

class StartTranslation
{
    private $settings;

    private $dataToTranslate;

    public function __construct()
    {
        $this->settings = new Settings();
        $this->dataToTranslate = [];
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function startTranslateExecute($post, $codeTo)
    {
        global $tsm;

        $result = $tsm->getClient()->checkCredential();
        if (!$result) {
            return seoSansMigraine_returnLoginError();
        }
        $codeFrom = $tsm->getPolylangManager()->getLanguageSlugForPost($post->ID);
        $this->prepareDataToTranslate($post, $codeTo);

        $result = $tsm->getClient()->startTranslation($this->dataToTranslate, $codeFrom, $codeTo, [
            "translateAssets" => $this->settings->settingIsEnabled(Settings::$KEYS["translateAssets"])
        ]);
        if ($result["success"]) {
            if (isset($result["data"]["backgroundProcess"])) {
                update_option("_seo_sans_migraine_backgroundProcess", $result["data"]["backgroundProcess"], false);
            }
        } else if (!empty($result) && isset($result["error"]) && $result["error"]["code"] === "U004403-001") {
            $result["data"] = [
                "reachedMaxQuota" => true,
                "estimatedQuota" => intval(explode(": ", $result["error"]["message"])[1])
            ];
        } else if (!empty($result) && isset($result["error"]) && ($result["error"]["code"] === "U004403-002" || $result["error"]["code"] === "U004403-003")) {
            $result["data"] = [
                "reachedMaxLanguages" => true,
            ];
        } else if (!empty($result) && isset($result["error"])) {
            $result["data"] = [
                "error" => $result["error"]
            ];
        }
        return [
            "success" => $result["success"],
            "data" => $result["data"]
        ];
    }

    private function prepareDataToTranslate($post, $codeTo)
    {
        global $tsm;

        $translatedPostId = $tsm->getPolylangManager()->getTranslationPost($post->ID, $codeTo);
        $translatedPost = $translatedPostId ? get_post($translatedPostId) : null;
        $willBeAnUpdate = $translatedPost !== null && !strstr($translatedPost->post_name, "-traduire-sans-migraine");
        $this->dataToTranslate = [];
        if (!empty($post->post_content)) {
            $this->dataToTranslate["content"] = $post->post_content;
        }
        if (!empty($post->post_title)) {
            $this->dataToTranslate["title"] = $post->post_title;
        }
        if (!empty($post->post_excerpt)) {
            $this->dataToTranslate["excerpt"] = $post->post_excerpt;
        }
        if (!empty($post->post_name) && !$willBeAnUpdate) {
            $this->dataToTranslate["slug"] = str_replace("-", " ", $post->post_name);
        }

        $postMetas = get_post_meta($post->ID);
        $this->handleYoast($postMetas, $willBeAnUpdate);
        $this->handleRankMath($postMetas, $willBeAnUpdate);
        $this->handleSeoPress($postMetas, $willBeAnUpdate);
        $this->handleElementor($postMetas);
        $this->handleACF($postMetas);
        $this->handleCategories($post->post_category, $codeTo);
    }

    private function handleYoast($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $metaTitle = $postMetas["_yoast_wpseo_title"][0] ?? "";
            if (!empty($metaTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaTitle"] = $metaTitle;
            }
            $metaDescription = $postMetas["_yoast_wpseo_metadesc"][0] ?? "";
            if (!empty($metaDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaDescription"] = $metaDescription;
            }
            $metaKeywords = $postMetas["_yoast_wpseo_metakeywords"][0] ?? "";
            if (!empty($metaKeywords) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaKeywords"] = $metaKeywords;
            }
            $focusKeyWords = $postMetas["_yoast_wpseo_focuskw"][0] ?? "";
            if (!empty($focusKeyWords) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["yoastFocusKeyword"] = $focusKeyWords;
            }
        }
    }

    private function handleRankMath($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $rankMathDescription = $postMetas["rank_math_description"][0] ?? "";
            if (!empty($rankMathDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathDescription"] = $rankMathDescription;
            }
            $rankMathTitle = $postMetas["rank_math_title"][0] ?? "";
            if (!empty($rankMathTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathTitle"] = $rankMathTitle;
            }
            $rankMathFocusKeyword = $postMetas["rank_math_focus_keyword"][0] ?? "";
            if (!empty($rankMathFocusKeyword) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathFocusKeyword"] = $rankMathFocusKeyword;
            }
        }
    }

    private function handleSeoPress($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("wp-seopress/seopress.php")) {
            $seopress_titles_desc = $postMetas["seopress_titles_desc"][0] ?? "";
            if (!empty($seopress_titles_desc) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
                $this->dataToTranslate["seopress_titles_desc"] = $seopress_titles_desc;
            }
            $seopress_titles_title = $postMetas["seopress_titles_title"][0] ?? "";
            if (!empty($seopress_titles_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
                $this->dataToTranslate["seopress_titles_title"] = $seopress_titles_title;
            }
            $seopress_analysis_target_kw = $postMetas["seopress_analysis_target_kw"][0] ?? "";
            if (!empty($seopress_analysis_target_kw) && (!$willBeAnUpdate || $this->settings->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
                $this->dataToTranslate["seopress_analysis_target_kw"] = $seopress_analysis_target_kw;
            }
        }
    }

    private function handleElementor($postMetas)
    {
        if (is_plugin_active("elementor/elementor.php")) {
            $noTranslateElementor = [
                "_elementor_code" => true,
                "_elementor_conditions" => true,
                "_elementor_controls_usage" => true,
                "_elementor_css" => true,
                "_elementor_edit_mode" => true,
                "_elementor_extra_options" => true,
                "_elementor_inline_svg" => true,
                "_elementor_location" => true,
                "_elementor_page_assets" => true,
                "_elementor_page_settings" => true,
                "_elementor_popup_display_settings" => true,
                "_elementor_priority" => true,
                "_elementor_pro_version" => true,
                "_elementor_screenshot_failed" => true,
                "_elementor_source" => true,
                "_elementor_source_image_hash" => true,
                "_elementor_template_sub_type" => true,
                "_elementor_template_type" => true,
                "_elementor_version" => true,
                "elementor_font_face" => true,
                "elementor_font_files" => true,
            ];
            foreach ($postMetas as $key => $value) {
                if (strstr($key, "elementor_") && !isset($noTranslateElementor[$key])) {
                    if (isset($value[0])) {
                        $this->dataToTranslate[$key] = $value[0];
                    }
                }
            }
        }
    }

    private function handleACF($postMetas)
    {
        if (is_plugin_active("advanced-custom-fields/acf.php")) {
            foreach ($postMetas as $key => $value) {
                if (isset($postMetas["_" . $key])) {
                    $this->dataToTranslate["acf_" . $key] = $value[0];
                }
            }
        }
    }

    public function handleCategories($categories, $codeTo)
    {
        global $tsm;
        if (!$this->settings->settingIsEnabled(Settings::$KEYS["translateCategories"])) {
            return;
        }
        foreach ($categories as $categoryId) {
            $result = $tsm->getPolylangManager()->getTranslationCategories([$categoryId], $codeTo);
            if (empty($result)) {
                $categoryName = get_cat_name($categoryId);
                $this->dataToTranslate["categories_" . $categoryId] = $categoryName;
            }
        }
    }
}