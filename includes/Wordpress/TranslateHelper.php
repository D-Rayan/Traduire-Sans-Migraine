<?php

namespace TraduireSansMigraine\Wordpress;


use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;

if (!defined("ABSPATH")) {
    exit;
}

class TranslateHelper
{
    private $tokenId;
    private $dataToTranslate;
    private $codeTo;
    private $codeFrom;
    private $originalPost;
    private $translatedPostId;


    private $error;
    private $success;

    private $languageManager;
    private $linkManager;

    public function __construct($tokenId, $translationData, $codeTo)
    {
        $this->tokenId = $tokenId;
        $this->dataToTranslate = $translationData;
        $this->codeTo = $codeTo;
        $this->languageManager = new LanguageManager();
        $this->linkManager = new LinkManager();
        $this->success = true;
    }

    public function handleTranslationResult()
    {
        $this->checkRequirements();
        if (!$this->success) {
            return;
        }
        $this->startTranslate();
    }

    private function startTranslate()
    {
        try {
            $this->codeFrom = $this->languageManager->getLanguageManager()->getLanguageForPost($this->originalPost->ID);
            if (isset($this->dataToTranslate["content"])) {
                $this->dataToTranslate["content"] = $this->linkManager->translateInternalLinks($this->dataToTranslate["content"], $this->codeFrom, $this->codeTo);
            }
            $this->dataToTranslate["categories"] = $this->languageManager->getLanguageManager()->getTranslationCategories($this->originalPost->post_category, $this->codeTo);
            $this->translatedPostId = $this->languageManager->getLanguageManager()->getTranslationPost($this->originalPost->ID, $this->codeTo);
            if (!$this->translatedPostId) {
                update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                    "percentage" => 100,
                    "status" => Step::$STEP_STATE["ERROR"],
                    "message" => [
                        "id" => TextDomain::_f("Oops! The otters have lost the post in the river. Please try again 🦦"),
                        "args" => []
                    ]
                ]);
                $this->success = false;
                return;
            }
            $translatedPost = get_post($this->translatedPostId);
            if (!$translatedPost) {
                update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                    "percentage" => 100,
                    "status" => Step::$STEP_STATE["ERROR"],
                    "message" => [
                        "id" => TextDomain::_f("Oops! The otters have lost the post in the river. Please try again 🦦"),
                        "args" => []
                    ]
                ]);
                $this->success = false;
                return;
            }
            if (strstr($translatedPost->post_name, "-traduire-sans-migraine")) {
                $this->updateTemporaryPostToRealOne();
            } else {
                $this->updatePost();
            }
            $this->handleYoast();
            $this->handleRankMath();
            $this->handleSEOPress();
            $this->handleElementor();
            $this->handleACF();
            $urlPost = get_admin_url(null, "post.php?post=" . $this->translatedPostId . "&action=edit");
            $htmlPost = "<a href='" . $urlPost . "' target='_blank'>" . $urlPost . "</a>";
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["DONE"],
                "message" => [
                    "id" => TextDomain::_f("The otters have finished the translation 🦦, Check it right here %s"),
                    "args" => [$htmlPost]
                ]
            ]);
            $this->success = true;
            $Queue = Queue::getInstance();
            $nextItem = $Queue->getNextItem();
            if (intval($nextItem["ID"]) === intval($this->originalPost->ID)) {
                $Queue->stopQueue();
                $nextItem["processed"] = true;
                $Queue->updateItem($nextItem);
                $Queue->startNextProcess();
            }
        } catch (\Exception $e) {
            $Queue = Queue::getInstance();
            $nextItem = $Queue->getNextItem();
            if (intval($nextItem["ID"]) === intval($this->originalPost->ID)) {
                $Queue->stopQueue();
                $nextItem["processed"] = true;
                $nextItem["data"] = ["message" => $e->getMessage(), "title" => "error", "logo" => "loutre_triste.png"];
                $nextItem["error"] = true;
                $Queue->updateItem($nextItem);
                $Queue->startNextProcess();
            }
            $this->success = false;
            $this->error = $e->getMessage();
        }
    }

    private function checkRequirements()
    {
        if ($this->dataToTranslate === false) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "message" => [
                    "id" => TextDomain::_f("Oops! The otters have lost the translation in the river. Please try again 🦦"),
                    "args" => []
                ]
            ]);
            $this->success = false;
            $this->error = "Data to translate not found";
            return;
        }
        $postId = get_option("_seo_sans_migraine_postId_" . $this->tokenId);
        update_option("_seo_sans_migraine_state_" . $this->tokenId, [
            "percentage" => 75,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "message" => [
                "id" => TextDomain::_f("The otters works on your SEO optimization 🦦"),
                "args" => []
            ]
        ]);
        if (!$postId) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "message" => [
                    "id" => TextDomain::_f("Oops! The otters have lost the post in the river. Please try again 🦦"),
                    "args" => []
                ]
            ]);
            delete_option("_seo_sans_migraine_postId_" . $this->tokenId);

            $this->success = false;
            $this->error = "Post not found";
            return;
        }
        $this->originalPost = get_post($postId);
        if (!$this->originalPost) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "message" => [
                    "id" => TextDomain::_f("Oops! The otters have lost the post in the river. Please try again 🦦"),
                    "args" => []
                ]
            ]);
            delete_option("_seo_sans_migraine_postId_" . $this->tokenId);
            $this->success = false;
            $this->error = "Post not found";
            return;
        }
    }



    private function updateTemporaryPostToRealOne() {
        global $wpdb;

        $this->dataToTranslate["slug"] = sanitize_title($this->dataToTranslate["slug"]);

        $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $this->dataToTranslate["slug"]);
        $exists = $wpdb->get_var($query);
        if (!empty($exists)) {
            $this->dataToTranslate["slug"] .= "-" . $this->codeTo . "-" . time();
        }

        $updatePostData = [
            'ID' => $this->translatedPostId,
            'post_title' => $this->dataToTranslate["title"],
            'post_content' => $this->dataToTranslate["content"],
            'post_category' => $this->dataToTranslate["categories"],
            'post_name' => $this->dataToTranslate["slug"],
        ];
        wp_update_post($updatePostData);

        $thumbnailId = get_post_meta($this->originalPost->ID, '_thumbnail_id', true);
        if (!empty($thumbnailId)) {
            update_post_meta($this->translatedPostId, '_thumbnail_id', $thumbnailId);
        }
        update_post_meta($this->translatedPostId, '_has_been_translated_by_tsm', "true");
        update_post_meta($this->translatedPostId, '_tsm_first_visit_after_translation', "true");
    }

    private function updatePost() {
        $updatePostData = [
            'ID' => $this->translatedPostId,
            'post_category' => $this->dataToTranslate["categories"]
        ];
        if (isset($this->dataToTranslate["content"])) {
            $updatePostData["post_content"] = $this->dataToTranslate["content"];
        }
        if (isset($this->dataToTranslate["title"])) {
            $updatePostData["post_title"] = $this->dataToTranslate["title"];
        }
        if (isset($this->dataToTranslate["excerpt"])) {
            $updatePostData["post_excerpt"] = $this->dataToTranslate["excerpt"];
        }
        if (isset($this->dataToTranslate["slug"])) {
            $updatePostData["post_name"] = sanitize_title($this->dataToTranslate["slug"]);
        }
        wp_update_post($updatePostData);
    }

    private function handleYoast() {
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            if (isset($this->dataToTranslate["metaTitle"])) {
                update_post_meta($this->translatedPostId, "_yoast_wpseo_title", $this->dataToTranslate["metaTitle"]);
            }
            if (isset($this->dataToTranslate["metaDescription"])) {
                update_post_meta($this->translatedPostId, "_yoast_wpseo_metadesc", $this->dataToTranslate["metaDescription"]);
            }
            if (isset($this->dataToTranslate["metaKeywords"])) {
                update_post_meta($this->translatedPostId, "_yoast_wpseo_metakeywords", $this->dataToTranslate["metaKeywords"]);
            }
        }
    }

    private function handleACF() {
        if (is_plugin_active("advanced-custom-fields/acf.php")) {
            $postMetas = get_post_meta($this->originalPost->ID);
            foreach ($this->dataToTranslate as $key => $value) {
                if (strstr($key, "acf_")) {
                    $key = substr($key, 4);
                    if (isset($postMetas[$key]) && isset($postMetas["_" . $key])) {
                        if ($this->is_json($value)) {
                            $value = wp_slash($value);
                        }
                        if ($this->is_serialized($value)) {
                            $value = unserialize($value);
                        }

                        update_post_meta($this->translatedPostId, $key, $value);
                        update_post_meta($this->translatedPostId, "_" . $key, $postMetas["_" . $key][0]);
                    }
                }
            }
        }
    }

    private function handleRankMath() {
        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            if (isset($this->dataToTranslate["rankMathDescription"])) {
                update_post_meta($this->translatedPostId, "rank_math_description", $this->dataToTranslate["rankMathDescription"]);
            }
            if (isset($this->dataToTranslate["rankMathTitle"])) {
                update_post_meta($this->translatedPostId, "rank_math_title", $this->dataToTranslate["rankMathTitle"]);
            }
            if (isset($this->dataToTranslate["rankMathFocusKeyword"])) {
                update_post_meta($this->translatedPostId, "rank_math_focus_keyword", $this->dataToTranslate["rankMathFocusKeyword"]);
            }
        }
    }

    private function handleSEOPress() {
        if (is_plugin_active("wp-seopress/seopress.php")) {
            if (isset($this->dataToTranslate["seopress_titles_desc"])) {
                update_post_meta($this->translatedPostId, "seopress_titles_desc", $this->dataToTranslate["seopress_titles_desc"]);
            }
            if (isset($this->dataToTranslate["seopress_titles_title"])) {
                update_post_meta($this->translatedPostId, "seopress_titles_title", $this->dataToTranslate["seopress_titles_title"]);
            }
            if (isset($this->dataToTranslate["seopress_analysis_target_kw"])) {
                update_post_meta($this->translatedPostId, "seopress_analysis_target_kw", $this->dataToTranslate["seopress_analysis_target_kw"]);
            }
        }
    }

    private function is_json($string) {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function is_serialized($string) {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }

    private function handleElementor() {
        if (is_plugin_active("elementor/elementor.php")) {
            $postMetas = get_post_meta($this->originalPost->ID);
            foreach ($postMetas as $key => $value) {
                if (strstr($key, "elementor")) {
                    $valueKey = isset($this->dataToTranslate[$key]) ? $this->dataToTranslate[$key] : $value[0];
                    $valueKey = $this->linkManager->translateInternalLinks($valueKey, $this->codeFrom, $this->codeTo);
                    if ($this->is_json($valueKey)) {
                        $valueKey = wp_slash($valueKey);
                    }
                    if ($this->is_serialized($valueKey)) {
                        $valueKey = unserialize($valueKey);
                    }
                    update_post_meta($this->translatedPostId, $key, $valueKey);
                }
            }
        }
    }

    public function getError()
    {
        return $this->error;
    }

    public function isSuccess()
    {
        return $this->success;
    }
}