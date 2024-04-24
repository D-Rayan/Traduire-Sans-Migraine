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
            echo json_encode(["success" => false, "error" => TextDomain::__("Post ID missing"), "data" => $_POST]);
            wp_die();
        }
        $result = $this->clientSeoSansMigraine->checkCredential();
        if (!$result) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token invalid")]);
            wp_die();
        }
        $postId = $_POST["post_id"];
        $languages = $_POST["languages"];
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
            echo json_encode(["success" => false, "error" => TextDomain::__("An error occurred during the creation of the translations")]);
            wp_die();
        }
        echo json_encode(["success" => true]);
        wp_die();
    }

    public function startTranslate() {
        if (!isset($_GET["post_id"]) || !isset($_GET["language"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post ID missing")]);
            wp_die();
        }
        $result = $this->clientSeoSansMigraine->checkCredential();
        if (!$result) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token invalid")]);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $codeTo = $_GET["language"];
        $post = get_post($postId);
        if (!$post) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post not found")]);
            wp_die();
        }
        $codeFrom = $this->languageManager->getLanguageManager()->getLanguageForPost($postId);
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($postId, $codeTo);
        $translatedPost = $translatedPostId ? get_post($translatedPostId) : null;
        $willBeAnUpdate = $translatedPost !== null && !strstr($translatedPost->post_name, "-traduire-sans-migraine");
        $dataToTranslate = [];
        if (!empty($post->post_content) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("content"))) { $dataToTranslate["content"] = $post->post_content; }
        if (!empty($post->post_title) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("title"))) { $dataToTranslate["title"] = $post->post_title; }
        if (!empty($post->post_excerpt) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("excerpt"))) { $dataToTranslate["excerpt"] = $post->post_excerpt; }
        if (!empty($post->post_name) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("slug"))) { $dataToTranslate["slug"] = $post->post_name; }

        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $metaTitle = get_post_meta($postId, "_yoast_wpseo_title", true);
            if ($metaTitle && !empty($metaTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_title"))) { $dataToTranslate["metaTitle"] = $metaTitle; }
            $metaDescription = get_post_meta($postId, "_yoast_wpseo_metadesc", true);
            if ($metaDescription && !empty($metaDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metadesc"))) { $dataToTranslate["metaDescription"] = $metaDescription; }
            $metaKeywords = get_post_meta($postId, "_yoast_wpseo_metakeywords", true);
            if ($metaKeywords && !empty($metaKeywords) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("_yoast_wpseo_metakeywords"))) { $dataToTranslate["metaKeywords"] = $metaKeywords; }
        }

        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $rankMathDescription = get_post_meta($postId, "rank_math_description", true);
            if ($rankMathDescription && !empty($rankMathDescription) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_description"))) { $dataToTranslate["rankMathDescription"] = $rankMathDescription; }
            $rankMathTitle = get_post_meta($postId, "rank_math_title", true);
            if ($rankMathTitle && !empty($rankMathTitle) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_title"))) { $dataToTranslate["rankMathTitle"] = $rankMathTitle; }
            $rankMathFocusKeyword = get_post_meta($postId, "rank_math_focus_keyword", true);
            if ($rankMathFocusKeyword && !empty($rankMathFocusKeyword) && (!$willBeAnUpdate || $this->settings->settingIsEnabled("rank_math_focus_keyword"))) { $dataToTranslate["rankMathFocusKeyword"] = $rankMathFocusKeyword; }
        }



        $result = $this->clientSeoSansMigraine->startTranslation($dataToTranslate, $codeFrom, $codeTo);
        if ($result["success"]) {
            $tokenId = $result["data"]["tokenId"];
            update_option("_seo_sans_migraine_state_" . $tokenId, [
                "percentage" => 50,
                "status" => Step::$STEP_STATE["PROGRESS"],
                "html" => TextDomain::__("The otters are translating your post 🦦"),
            ]);
            update_option("_seo_sans_migraine_postId_" . $tokenId, $postId);
        }
        echo json_encode($result);
        wp_die();
    }

    public function getTranslateState() {
        if (!isset($_GET["tokenId"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token ID missing")]);
            wp_die();
        }
        $tokenId = $_GET["tokenId"];
        $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
            "percentage" => 25,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "html" => TextDomain::__("We will create and translate your post 💡"),
        ]);
        if (!$state) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token not found")]);
            wp_die();
        }
        if (isset($state["status"]) && $state["status"] === Step::$STEP_STATE["DONE"]) {
            delete_option("_seo_sans_migraine_state_" . $tokenId);
            delete_option("_seo_sans_migraine_postId_" . $tokenId);
        }
        echo json_encode(["success" => true, "data" => $state]);
        wp_die();
    }

    public function getTranslatedPostId() {
        if (!isset($_GET["post_id"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post ID missing")]);
            wp_die();
        }
        if (!isset($_GET["language"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Language missing")]);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $language = $_GET["language"];
        $translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($postId, $language);
        echo json_encode(["success" => true, "data" => $translatedPostId]);
        wp_die();
    }
}