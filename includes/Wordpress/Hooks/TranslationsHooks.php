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

class TranslationsHooks {
    private $clientSeoSansMigraine;
    private $languageManager;
    private $settings;
    public function __construct()
    {
        $this->clientSeoSansMigraine = new Client();
        $this->languageManager = new LanguageManager();
        $this->settings = new Settings();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_prepare_translate", [$this, "prepareTranslation"]);
        add_action("wp_ajax_traduire-sans-migraine_editor_start_translate", [$this, "startTranslate"]);
        add_action("wp_ajax_traduire-sans-migraine_editor_get_state_translate", [$this, "getTranslateState"]);
        add_action("wp_ajax_traduire-sans-migraine_editor_get_post_translated", [$this, "getTranslatedPostId"]);
    }

    public function loadHooks() {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }
    public function init() {
        $this->loadHooks();
    }

    public function prepareTranslation() {
        global $wpdb;
        if (!isset($_POST["post_id"]) || !isset($_POST["languages"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post or the languages asked. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $result = $this->prepareTranslationExecute($_POST["post_id"], $_POST["languages"]);
        if ($result["success"]) {
            wp_send_json_success($result["data"]);
        } else {
            wp_send_json_error($result["data"], 400);
        }
        wp_die();
    }

    public function prepareTranslationExecute($postId, $languages) {
        global $wpdb;
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
        $originalTranslations = $this->languageManager->getLanguageManager()->getAllTranslationsPost($postId);
        $translations = [];
        $originalPost = get_post($postId);
        foreach ($originalTranslations as $slug => $translation) {
            if ($translation["postId"]) {
                $translations[$slug] = $translation["postId"];
            } else if (in_array($slug, $languages)) {
                $temporaryNamePost = "post-" . $postId . "-" . $slug."-traduire-sans-migraine";
                $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $temporaryNamePost);
                $exists = $wpdb->get_var($query);
                if (!empty($exists)) {
                    $temporaryNamePost .= "-" . time();
                }

                $translations[$slug] = wp_insert_post([
                    'post_title' => "Translation of post " . $postId . " in " . $slug,
                    'post_content' => "This content is temporary... It will be either deleted or updated soon.",
                    'post_author' => $originalPost->post_author,
                    'post_type' => $originalPost->post_type,
                    'post_name' => $temporaryNamePost,
                    'post_status' => 'draft'
                ], true);
            }
        }
        $this->languageManager->getLanguageManager()->saveAllTranslationsPost($translations);
        $updatedTranslations = $this->languageManager->getLanguageManager()->getAllTranslationsPost($postId);
        $errorCreationTranslations = false;
        foreach ($languages as $slug) {
            if (isset($updatedTranslations[$slug]["postId"]) && !empty(isset($updatedTranslations[$slug]["postId"]))) {
                continue;
            }
            $errorCreationTranslations = true;
            if (isset($translations[$slug])) { wp_delete_post($translations[$slug], true); }
        }
        if ($errorCreationTranslations) {
            return [
                "success" => false,
                "data" => [
                    "title" => TextDomain::__("An error occurred"),
                    "message" => TextDomain::__("We could not create all the translations. Please try again."),
                    "logo" => "loutre_triste.png"
                ]
            ];
        }
        return ["success" => true, "data" => []];
    }

    public function startTranslate() {
        if (!isset($_GET["post_id"]) || !isset($_GET["language"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post or the language asked. Please try again."),
                "logo" => "loutre_triste.png"
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
            wp_send_json_success($result["data"]);
        } else {
            wp_send_json_error($result["data"], 400);
        }
    }

    private function getDataToTranslate($post, $codeTo) {
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($post->ID, $codeTo);
        $translatedPost = $translatedPostId ? get_post($translatedPostId) : null;
        $willBeAnUpdate = $translatedPost !== null && !strstr($translatedPost->post_name, "-traduire-sans-migraine");
        $dataToTranslate = [];
        if (!empty($post->post_content) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("content"))) { $dataToTranslate["content"] = $post->post_content; }
        if (!empty($post->post_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("title"))) { $dataToTranslate["title"] = $post->post_title; }
        if (!empty($post->post_excerpt) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("excerpt"))) { $dataToTranslate["excerpt"] = $post->post_excerpt; }
        if (!empty($post->post_name) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("slug"))) { $dataToTranslate["slug"] = str_replace("-", " ", $post->post_name); }


        $postMetas = get_post_meta($post->ID);
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $metaTitle = isset($postMetas["_yoast_wpseo_title"][0]) ? $postMetas["_yoast_wpseo_title"][0] : "";
            if ($metaTitle && !empty($metaTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_title"))) { $dataToTranslate["metaTitle"] = $metaTitle; }
            $metaDescription = isset($postMetas["_yoast_wpseo_metadesc"][0]) ? $postMetas["_yoast_wpseo_metadesc"][0] : "";
            if ($metaDescription && !empty($metaDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metadesc"))) { $dataToTranslate["metaDescription"] = $metaDescription; }
            $metaKeywords = isset($postMetas["_yoast_wpseo_metakeywords"][0]) ? $postMetas["_yoast_wpseo_metakeywords"][0] : "";
            if ($metaKeywords && !empty($metaKeywords) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metakeywords"))) { $dataToTranslate["metaKeywords"] = $metaKeywords; }
        }

        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $rankMathDescription = isset($postMetas["rank_math_description"][0]) ? $postMetas["rank_math_description"][0] : "";
            if ($rankMathDescription && !empty($rankMathDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_description"))) { $dataToTranslate["rankMathDescription"] = $rankMathDescription; }
            $rankMathTitle = isset($postMetas["rank_math_title"][0]) ? $postMetas["rank_math_title"][0] : "";
            if ($rankMathTitle && !empty($rankMathTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_title"))) { $dataToTranslate["rankMathTitle"] = $rankMathTitle; }
            $rankMathFocusKeyword = isset($postMetas["rank_math_focus_keyword"][0]) ? $postMetas["rank_math_focus_keyword"][0] : "";
            if ($rankMathFocusKeyword && !empty($rankMathFocusKeyword) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_focus_keyword"))) { $dataToTranslate["rankMathFocusKeyword"] = $rankMathFocusKeyword; }
        }

        if (is_plugin_active("wp-seopress/seopress.php")) {
            $seopress_titles_desc = isset($postMetas["seopress_titles_desc"][0]) ? $postMetas["seopress_titles_desc"][0] : "";
            if ($seopress_titles_desc && !empty($seopress_titles_desc) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_titles_desc"))) { $dataToTranslate["seopress_titles_desc"] = $seopress_titles_desc; }
            $seopress_titles_title = isset($postMetas["seopress_titles_title"][0]) ? $postMetas["seopress_titles_title"][0] : "";
            if ($seopress_titles_title && !empty($seopress_titles_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_titles_title"))) { $dataToTranslate["seopress_titles_title"] = $seopress_titles_title; }
            $seopress_analysis_target_kw = isset($postMetas["seopress_analysis_target_kw"][0]) ? $postMetas["seopress_analysis_target_kw"][0] : "";
            if ($seopress_analysis_target_kw && !empty($seopress_analysis_target_kw) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("seopress_analysis_target_kw"))) { $dataToTranslate["seopress_analysis_target_kw"] = $seopress_analysis_target_kw; }
        }

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
                        $dataToTranslate[$key] = $value[0];
                    }
                }
            }
        }
        return $dataToTranslate;
    }
    public function startTranslateExecute($post, $codeTo) {
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
        $dataToTranslate = $this->getDataToTranslate($post, $codeTo);

        $result = $this->clientSeoSansMigraine->startTranslation($dataToTranslate, $codeFrom, $codeTo);
        if ($result["success"]) {
            $tokenId = $result["data"]["tokenId"];
            update_option("_seo_sans_migraine_state_" . $tokenId, [
                "percentage" => 50,
                "status" => Step::$STEP_STATE["PROGRESS"],
                "message" => [
                    "id" => "The otters are translating your post 🦦",
                    "args" => []
                ]
            ]);
            update_option("_seo_sans_migraine_postId_" . $tokenId, $post->ID);
        }
        if (isset($result["error"]) && $result["error"]["code"] === "U004403-001") {
            $result["data"] = [
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("You have reached your monthly quota."),
                "logo" => "loutre_triste.png"
            ];
        }
        return [
            "success" => $result["success"],
            "data" => $result["data"]
        ];
    }

    public function getTranslateState() {
        if (!isset($_GET["tokenId"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the request ID."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $tokenId = $_GET["tokenId"];
        $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
            "percentage" => 25,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "message" => [
                "id" => "We will create and translate your post 💡",
                "args" => []
            ]
        ]);
        if (!$state) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the state of the translation"),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        if (isset($state["status"]) && $state["status"] === Step::$STEP_STATE["DONE"]) {
            delete_option("_seo_sans_migraine_state_" . $tokenId);
            delete_option("_seo_sans_migraine_postId_" . $tokenId);
        }
        if (isset($state["message"])) {
            $state["html"] = TextDomain::__($state["message"]["id"], ...$state["message"]["args"]);
        }
        echo json_encode(["success" => true, "data" => $state]);
        wp_die();
    }

    public function getTranslatedPostId() {
        if (!isset($_GET["post_id"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find your post. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["language"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the language. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $language = $_GET["language"];
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($postId, $language);
        echo json_encode(["success" => true, "data" => $translatedPostId]);
        wp_die();
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}