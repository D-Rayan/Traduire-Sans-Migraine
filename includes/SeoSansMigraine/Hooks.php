<?php

namespace TraduireSansMigraine\SeoSansMigraine;


use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\LinkManager;
use WP_REST_Response;

if (!defined("ABSPATH")) {
    exit;
}

class Hooks
{
    public function __construct() {

    }

    public function init() {
        add_action( 'rest_api_init', [$this, "registerEndpoints"]);
    }

    public function setTranslations($data) {
        global $wpdb;
        try {
            if (!isset($data["id"]) || !isset($data["dataToTranslate"]) || !isset($data["codeTo"])) {
                return new WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
            }
            $tokenId = $data["id"];
            $dataToTranslate = $data["dataToTranslate"];
            $codeTo = $data["codeTo"];
            if ($dataToTranslate === false) {
                update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "error"]);
                return new WP_REST_Response(["success" => false, "error" => "Data to translate not found"], 400);
            }
            $postId = get_option("_seo_sans_migraine_postId_" . $tokenId);
            update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "success", 4 => "pending"]);
            if (!$postId) {
                update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "success", 4 => "error"]);
                delete_option("_seo_sans_migraine_postId_" . $tokenId);
                return new WP_REST_Response(["success" => false, "error" => "Post not found"], 404);
            }
            $originalPost = get_post($postId);
            if (!$originalPost) {
                update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "success", 4 => "error"]);
                delete_option("_seo_sans_migraine_postId_" . $tokenId);
                return new WP_REST_Response(["success" => false, "error" => "Post not found"], 404);
            }
            $languageManager = new LanguageManager();
            $translatedPostId = $languageManager->getLanguageManager()->getTranslationPost($postId, $codeTo);
            $codeFrom = $languageManager->getLanguageManager()->getLanguageForPost($postId);
            $dataToTranslate["categories"] = $languageManager->getLanguageManager()->getTranslationCategories($originalPost->post_category, $codeTo);
            if (isset($dataToTranslate["content"])) {
                $linkManager = new LinkManager();
                $dataToTranslate["content"] = $linkManager->translateInternalLinks($dataToTranslate["content"], $codeFrom, $codeTo);
            }
            if (!$translatedPostId) {
                $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $dataToTranslate["slug"]);
                $exists = $wpdb->get_var($query);
                if (!empty($exists)) {
                    $dataToTranslate["slug"] .= "-" . $codeTo . "-" . time();
                }

                $translatedPostId = wp_insert_post([
                    'post_title' => $dataToTranslate["title"],
                    'post_content' => $dataToTranslate["content"],
                    'post_author' => $originalPost->post_author,
                    'post_category' => $dataToTranslate["categories"],
                    'post_type' => $originalPost->post_type,
                    'post_name' => $dataToTranslate["slug"],
                    'post_status' => 'draft'
                ], true);

                if (!$translatedPostId) {
                    update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "success", 4 => "error"]);
                    return new WP_REST_Response(["success" => false, "error" => "Could not create post"], 500);
                }
                $thumbnailId = get_post_meta($originalPost->ID, '_thumbnail_id', true);
                if (!empty($thumbnailId)) {
                    update_post_meta($translatedPostId, '_thumbnail_id', $thumbnailId);
                }
                pll_set_post_language($translatedPostId, $codeTo);
                $languageManager->getLanguageManager()->setTranslationPost($postId, $codeTo, $translatedPostId);
            } else {
                $updatePostData = [
                    'ID' => $postId,
                    'post_category' => $dataToTranslate["categories"]
                ];
                if (isset($dataToTranslate["content"])) {
                    $updatePostData["post_content"] = $dataToTranslate["content"];
                }
                if (isset($dataToTranslate["title"])) {
                    $updatePostData["post_title"] = $dataToTranslate["title"];
                }
                if (isset($dataToTranslate["excerpt"])) {
                    $updatePostData["post_excerpt"] = $dataToTranslate["excerpt"];
                }
                if (isset($dataToTranslate["slug"])) {
                    $updatePostData["post_name"] = $dataToTranslate["slug"];
                }
                wp_update_post($updatePostData);
            }
            if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php")) {
                if (isset($dataToTranslate["metaTitle"])) {
                    update_post_meta($translatedPostId, "_yoast_wpseo_title", $dataToTranslate["metaTitle"]);
                }
                if (isset($dataToTranslate["metaDescription"])) {
                    update_post_meta($translatedPostId, "_yoast_wpseo_metadesc", $dataToTranslate["metaDescription"]);
                }
                if (isset($dataToTranslate["metaKeywords"])) {
                    update_post_meta($translatedPostId, "_yoast_wpseo_metakeywords", $dataToTranslate["metaKeywords"]);
                }
            }
            update_option("_seo_sans_migraine_state_" . $tokenId, [1 => "success", 2 => "success", 3 => "success", 4 => "success"]);


            return new WP_REST_Response($data, 200);
        } catch (\Exception $e) {
            return new WP_REST_Response(["success" => false, "error" => $e->getMessage()], 500);
        }
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