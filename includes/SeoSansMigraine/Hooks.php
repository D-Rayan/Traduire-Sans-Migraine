<?php

namespace TraduireSansMigraine\SeoSansMigraine;


use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\TextDomain;
use \WP_REST_Response;

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
                $this->createPost();
            } else {
                $this->updatePost();
            }
            $this->handleYoast();
            $this->handleRankMath();
            $urlPost = get_admin_url(null, "post.php?post=" . $this->translatedPostId . "&action=edit");
            $htmlPost = "<a href='".$urlPost."' target='_blank'>".$urlPost."</a>";
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["DONE"],
                "html" => TextDomain::__("The otters have finished the translation 🦦, Check it right here %s", $htmlPost),
            ]);

            return new \WP_REST_Response($data, 200);
        } catch (\Exception $e) {
            return new \WP_REST_Response(["success" => false, "error" => $e->getMessage()], 500);
        }
    }

    private function createPost() {
        global $wpdb;

        $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $this->dataToTranslate["slug"]);
        $exists = $wpdb->get_var($query);
        if (!empty($exists)) {
            $this->dataToTranslate["slug"] .= "-" . $this->codeTo . "-" . time();
        }

        $this->translatedPostId = wp_insert_post([
            'post_title' => $this->dataToTranslate["title"],
            'post_content' => $this->dataToTranslate["content"],
            'post_author' => $this->originalPost->post_author,
            'post_category' => $this->dataToTranslate["categories"],
            'post_type' => $this->originalPost->post_type,
            'post_name' => $this->dataToTranslate["slug"],
            'post_status' => 'draft'
        ], true);

        if (!$this->translatedPostId) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "html" => TextDomain::__("Oops! The otters couldn't create the new post. Please try again 🦦"),
            ]);
            return new \WP_REST_Response(["success" => false, "error" => "Could not create post"], 500);
        }
        $thumbnailId = get_post_meta($this->originalPost->ID, '_thumbnail_id', true);
        if (!empty($thumbnailId)) {
            update_post_meta($this->translatedPostId, '_thumbnail_id', $thumbnailId);
        }
        $this->languageManager->getLanguageManager()->setTranslationPost($this->originalPost->ID, $this->codeTo, $this->translatedPostId);
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
            $updatePostData["post_name"] = $this->dataToTranslate["slug"];
        }
        wp_update_post($updatePostData);
    }

    private function handleYoast() {
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php")) {
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
        if (is_plugin_active("seo-by-rank-math/rank-math.php")) {
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
                "html" => TextDomain::__("Oops! The otters have lost the translation in the river. Please try again 🦦"),
            ]);
            return new \WP_REST_Response(["success" => false, "error" => "Data to translate not found"], 400);
        }
        $postId = get_option("_seo_sans_migraine_postId_" . $this->tokenId);
        update_option("_seo_sans_migraine_state_" . $this->tokenId, [
            "percentage" => 75,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "html" => TextDomain::__("The otters works on your SEO optimization 🦦"),
        ]);
        if (!$postId) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "html" => TextDomain::__("Oops! The otters have lost the post in the river. Please try again 🦦"),
            ]);
            delete_option("_seo_sans_migraine_postId_" . $this->tokenId);
            return new \WP_REST_Response(["success" => false, "error" => "Post not found"], 404);
        }
        $this->originalPost = get_post($postId);
        if (!$this->originalPost) {
            update_option("_seo_sans_migraine_state_" . $this->tokenId, [
                "percentage" => 100,
                "status" => Step::$STEP_STATE["ERROR"],
                "html" => TextDomain::__("Oops! The otters have lost the post in the river. Please try again 🦦"),
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