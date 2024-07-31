<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class StartTranslation
{
    private $clientSeoSansMigraine;
    private $languageManager;
    private $settings;

    private $dataToTranslate;

    public function __construct()
    {
        $this->clientSeoSansMigraine = new Client();
        $this->languageManager = new LanguageManager();
        $this->settings = new Settings();
        $this->dataToTranslate = [];
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function loadHooksAdmin()
    {
        add_action("wp_ajax_traduire-sans-migraine_editor_start_translate", [$this, "startTranslate"]);
    }

    public function loadHooks()
    {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function startTranslate()
    {
        if (!isset($_GET["post_id"]) || !isset($_GET["language"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post or the language asked. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_editor_start_translate_" . $_GET["language"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $codeTo = $_GET["language"];
        $post = get_post($postId);
        if (!$post) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $result = $this->startTranslateExecute($post, $codeTo);
        if ($result["success"]) {
            $result["data"]["wpNonce"] = wp_create_nonce("traduire-sans-migraine_editor_get_state_translate");
            wp_send_json_success($result["data"]);
        } else {
            wp_send_json_error($result, 400);
        }
    }

    private function handleYoast($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $metaTitle = $postMetas["_yoast_wpseo_title"][0] ?? "";
            if (!empty($metaTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_title"))) {
                $this->dataToTranslate["metaTitle"] = $metaTitle;
            }
            $metaDescription = $postMetas["_yoast_wpseo_metadesc"][0] ?? "";
            if (!empty($metaDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metadesc"))) {
                $this->dataToTranslate["metaDescription"] = $metaDescription;
            }
            $metaKeywords = $postMetas["_yoast_wpseo_metakeywords"][0] ?? "";
            if (!empty($metaKeywords) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metakeywords"))) {
                $this->dataToTranslate["metaKeywords"] = $metaKeywords;
            }
        }
    }

    private function handleRankMath($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $rankMathDescription = $postMetas["rank_math_description"][0] ?? "";
            if (!empty($rankMathDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_description"))) {
                $this->dataToTranslate["rankMathDescription"] = $rankMathDescription;
            }
            $rankMathTitle = $postMetas["rank_math_title"][0] ?? "";
            if (!empty($rankMathTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_title"))) {
                $this->dataToTranslate["rankMathTitle"] = $rankMathTitle;
            }
            $rankMathFocusKeyword = $postMetas["rank_math_focus_keyword"][0] ?? "";
            if (!empty($rankMathFocusKeyword) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_focus_keyword"))) {
                $this->dataToTranslate["rankMathFocusKeyword"] = $rankMathFocusKeyword;
            }
        }
    }

    private function handleSeoPress($postMetas, $willBeAnUpdate)
    {
        if (is_plugin_active("wp-seopress/seopress.php")) {
            $seopress_titles_desc = $postMetas["seopress_titles_desc"][0] ?? "";
            if (!empty($seopress_titles_desc) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_titles_desc"))) {
                $this->dataToTranslate["seopress_titles_desc"] = $seopress_titles_desc;
            }
            $seopress_titles_title = $postMetas["seopress_titles_title"][0] ?? "";
            if (!empty($seopress_titles_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_titles_title"))) {
                $this->dataToTranslate["seopress_titles_title"] = $seopress_titles_title;
            }
            $seopress_analysis_target_kw = $postMetas["seopress_analysis_target_kw"][0] ?? "";
            if (!empty($seopress_analysis_target_kw) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_analysis_target_kw"))) {
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

    private function prepareDataToTranslate($post, $codeTo)
    {
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($post->ID, $codeTo);
        $translatedPost = $translatedPostId ? get_post($translatedPostId) : null;
        $willBeAnUpdate = $translatedPost !== null && !strstr($translatedPost->post_name, "-traduire-sans-migraine");
        $this->dataToTranslate = [];
        if (!empty($post->post_content) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("content"))) {
            $this->dataToTranslate["content"] = $post->post_content;
        }
        if (!empty($post->post_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("title"))) {
            $this->dataToTranslate["title"] = $post->post_title;
        }
        if (!empty($post->post_excerpt) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("excerpt"))) {
            $this->dataToTranslate["excerpt"] = $post->post_excerpt;
        }
        if (!empty($post->post_name) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("slug"))) {
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

    public function handleCategories($categories, $codeTo)
    {
        foreach ($categories as $categoryId) {
            $result = $this->languageManager->getLanguageManager()->getTranslationCategories([$categoryId], $codeTo);
            if (empty($result)) {
                $categoryName = get_cat_name($categoryId);
                $this->dataToTranslate["categories_". $categoryId] = $categoryName;
            }
        }
    }

    public function startTranslateExecute($post, $codeTo)
    {
        $result = $this->clientSeoSansMigraine->checkCredential();
        if (!$result) {
            return [
                "success" => false,
                "data" => [
                    "title" => TextDomain::__("An error occurred"),
                    "message" => TextDomain::__("We could not authenticate you. Please check the plugin settings."),
                    "logo" => "loutre_triste.png"
                ]
            ];
        }
        $codeFrom = $this->languageManager->getLanguageManager()->getLanguageForPost($post->ID);
        $this->prepareDataToTranslate($post, $codeTo);

        $result = $this->clientSeoSansMigraine->startTranslation($this->dataToTranslate, $codeFrom, $codeTo);
        if ($result["success"]) {
            $tokenId = $result["data"]["tokenId"];
            update_option("_seo_sans_migraine_state_" . $tokenId, [
                "percentage" => 50,
                "status" => Step::$STEP_STATE["PROGRESS"],
                "message" => [
                    "id" => TextDomain::_f("The otters are translating your post 🦦"),
                    "args" => []
                ]
            ]);
            update_option("_seo_sans_migraine_postId_" . $tokenId, $post->ID);
            if (isset($result["data"]["backgroundProcess"])) {
                update_option("_seo_sans_migraine_backgroundProcess", $result["data"]["backgroundProcess"]);
            }
        } else if (!empty($result) && isset($result["error"]) && $result["error"]["code"] === "U004403-001") {
            $result["data"] = [
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("You have reached your monthly quota."),
                "logo" => "loutre_triste.png",
                "buttons" => [
                    [
                        "label" => TextDomain::__("Get more credits"),
                        "type" => "primary",
                        "url" => TSM__CLIENT_LOGIN_DOMAIN . "?key=" . $this->settings->getToken()
                    ]
                ],
                "semi-persist" => true
            ];
        } else if (!empty($result) && isset($result["error"]) && ($result["error"]["code"] === "U004403-002" || $result["error"]["code"] === "U004403-003")) {
            $result["data"] = [
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("You have reached your languages quota."),
                "logo" => "loutre_triste.png",
                "buttons" => [
                    [
                        "label" => TextDomain::__("Check my account"),
                        "type" => "primary",
                        "url" => TSM__CLIENT_LOGIN_DOMAIN . "?key=" . $this->settings->getToken()
                    ]
                ],
                "persist" => true
            ];
        } else if (!empty($result) && isset($result["error"])) {
            $result["data"] = [
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("It's a bit weird, but we could not translate your post. Please try again."),
                "logo" => "loutre_triste.png",
                "persist" => false,
                "error" => $result["error"]
            ];
        }
        return [
            "success" => $result["success"],
            "data" => $result["data"],
            "debug" => $result
        ];
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}