<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractPrepareTranslation;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends AbstractPrepareTranslation
{
    public function prepareDataToTranslate()
    {
        $post = $this->object;
        $translations = TranslationPost::findTranslationFor($post->ID);
        $translatedPostId = !empty($this->codeTo) ? $translations->getTranslation($this->codeTo) : false;
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
    }

    private function handleYoast($postMetas, $willBeAnUpdate)
    {
        global $tsm;
        if ($tsm->getSettings()->settingIsEnabled(Settings::$KEYS["yoastSEO"])) {
            $metaTitle = $postMetas["_yoast_wpseo_title"][0] ?? "";
            if (!empty($metaTitle) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaTitle"] = $metaTitle;
            }
            $metaDescription = $postMetas["_yoast_wpseo_metadesc"][0] ?? "";
            if (!empty($metaDescription) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaDescription"] = $metaDescription;
            }
            $metaKeywords = $postMetas["_yoast_wpseo_metakeywords"][0] ?? "";
            if (!empty($metaKeywords) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["metaKeywords"] = $metaKeywords;
            }
            $focusKeyWords = $postMetas["_yoast_wpseo_focuskw"][0] ?? "";
            if (!empty($focusKeyWords) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["yoastSEO"]))) {
                $this->dataToTranslate["yoastFocusKeyword"] = $focusKeyWords;
            }
        }
    }

    private function handleRankMath($postMetas, $willBeAnUpdate)
    {
        global $tsm;
        if ($tsm->getSettings()->settingIsEnabled(Settings::$KEYS["rankMath"])) {
            $rankMathDescription = $postMetas["rank_math_description"][0] ?? "";
            if (!empty($rankMathDescription) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathDescription"] = $rankMathDescription;
            }
            $rankMathTitle = $postMetas["rank_math_title"][0] ?? "";
            if (!empty($rankMathTitle) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathTitle"] = $rankMathTitle;
            }
            $rankMathFocusKeyword = $postMetas["rank_math_focus_keyword"][0] ?? "";
            if (!empty($rankMathFocusKeyword) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["rankMath"]))) {
                $this->dataToTranslate["rankMathFocusKeyword"] = $rankMathFocusKeyword;
            }
        }
    }

    private function handleSeoPress($postMetas, $willBeAnUpdate)
    {
        global $tsm;
        if ($tsm->getSettings()->settingIsEnabled(Settings::$KEYS["SEOPress"])) {
            $seopress_titles_desc = $postMetas["seopress_titles_desc"][0] ?? "";
            if (!empty($seopress_titles_desc) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
                $this->dataToTranslate["seopress_titles_desc"] = $seopress_titles_desc;
            }
            $seopress_titles_title = $postMetas["seopress_titles_title"][0] ?? "";
            if (!empty($seopress_titles_title) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
                $this->dataToTranslate["seopress_titles_title"] = $seopress_titles_title;
            }
            $seopress_analysis_target_kw = $postMetas["seopress_analysis_target_kw"][0] ?? "";
            if (!empty($seopress_analysis_target_kw) && (!$willBeAnUpdate || $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["SEOPress"]))) {
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
                "_elementor_element_cache" => true
            ];
            if (!isset($postMetas["_elementor_edit_mode"])) {
                return;
            }
            foreach ($postMetas as $key => $value) {
                if (strstr($key, "elementor_") && !isset($noTranslateElementor[$key])) {
                    if (isset($value[0]) && !empty($value[0])) {
                        $this->dataToTranslate[$key] = $value[0];
                        if ($key === "_elementor_data") {
                            $this->dataToTranslate["content"] = "";
                        }
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

    protected function getSlugOrigin()
    {
        $language = LanguagePost::getLanguage($this->object->ID);
        if (empty($language)) {
            return null;
        }
        return $language["code"];
    }
}
