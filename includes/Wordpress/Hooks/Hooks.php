<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class Hooks
{
    private $languageManager;
    private $tokenId;
    private $dataToTranslate;
    private $codeTo;
    private $codeFrom;
    private $originalPost;

    private $translatedPostId;

    private $linkManager;

    public function __construct() {
        $this->languageManager = new LanguageManager();
        $this->linkManager = new LinkManager();
    }

    public function init() {
        add_action( 'rest_api_init', [$this, "registerEndpoints"]);
    }

    public function setTranslations($data) {
        set_time_limit(0); // Can be a long process cause of the lock system
        try {
            $response = $this->checkRequirements($data);
            if ($response !== true) {
                return $response;
            }
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
            $urlPost = get_admin_url(null, "post.php?post=" . $this->translatedPostId . "&action=edit");
            $htmlPost = "<a href='".$urlPost."' target='_blank'>".$urlPost."</a>";
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["DONE"],
                "message" => [
                    "id" => TextDomain::_f("The otters have finished the translation 🦦, Check it right here %s"),
                    "args" => [$htmlPost]
                ]
            ]);

            $response = new \WP_REST_Response($data, 200);
        } catch (\Exception $e) {
            $response = new \WP_REST_Response(["success" => false, "error" => $e->getMessage()], 500);
        }

        if (Queue::getInstance()->isFromQueue($this->originalPost->ID)) {
            Queue::getInstance()->stopQueue();
            Queue::getInstance()->updateItem(["processed" => true, "ID" => $this->originalPost->ID]);
            Queue::getInstance()->startNextProcess();
        }

        return $response;
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

    private function checkRequirements($data) {
        if (!isset($data["id"]) || !isset($data["dataToTranslate"]) || !isset($data["codeTo"])) {
            return new \WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
        }
        $this->tokenId = $data["id"];
        $this->dataToTranslate = $data["dataToTranslate"];
        $this->codeTo = $data["codeTo"];

        if ($this->dataToTranslate === false) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "message" => [
                    "id" => TextDomain::_f("Oops! The otters have lost the translation in the river. Please try again 🦦"),
                    "args" => []
                ]
            ]);
            return new \WP_REST_Response(["success" => false, "error" => "Data to translate not found"], 400);
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
            return new \WP_REST_Response(["success" => false, "error" => "Post not found"], 404);
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
            return new \WP_REST_Response(["success" => false, "error" => "Post not found"], 404);
        }

        return true;
    }

    public function registerEndpoints() {
        register_rest_route( 'seo-sans-migraine', '/translations/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, "setTranslations"],
            'permission_callback' => function () {
                return true;
            },
            'args' => [
                'id' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
                'dataToTranslate' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
                'codeTo' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
            ],
        ]);
    }
}