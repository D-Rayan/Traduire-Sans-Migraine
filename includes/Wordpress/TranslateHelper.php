<?php

namespace TraduireSansMigraine\Wordpress;


use Exception;
use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Object\Action;
use WP_Error;

if (!defined("ABSPATH")) {
    exit;
}

class TranslateHelper
{
    private $dataToTranslate;
    private $codeTo;
    private $codeFrom;
    private $originalPost;
    private $translatedPostId;
    private $polylangManager;
    private $linkManager;

    private $postMetas;

    private $action;

    public function __construct($tokenId, $translationData, $codeTo)
    {
        $this->dataToTranslate = $translationData;
        $this->codeTo = $codeTo;
        $this->polylangManager = new PolylangManager();
        $this->linkManager = new LinkManager();
        $this->action = Action::loadByToken($tokenId);
    }

    public function handleTranslationResult()
    {
        if (!$this->action) {
            return;
        }
        $this->checkRequirements();
        if ($this->action->getState() !== DAOActions::$STATE["PROCESSING"]) {
            return;
        }
        $this->startTranslate();
    }

    private function checkRequirements()
    {
        if (!$this->action) {
            return;
        }
        if ($this->dataToTranslate === false) {
            $this->action->setAsError()->setResponse(["error" => "Data to translate not found"])->save();
            return;
        }
        $this->originalPost = get_post($this->action->getPostId());
        if (!$this->originalPost) {
            $this->action->setAsError()->setResponse(["error" => "Post not found"])->save();
            return;
        }
        $this->action->setResponse(["percentage" => 75])->save();
    }

    private function startTranslate()
    {
        try {
            $this->codeFrom = $this->polylangManager->getLanguageSlugForPost($this->originalPost->ID);
            if (isset($this->dataToTranslate["content"])) {
                $this->dataToTranslate["content"] = $this->linkManager->translateInternalLinks($this->dataToTranslate["content"], $this->codeFrom, $this->codeTo);
                $this->handleAssetsTranslations();
            }
            foreach ($this->originalPost->post_category as $termId) {
                $result = $this->polylangManager->getTranslationCategories([$termId], $this->codeTo);
                if (empty($result) && isset($this->dataToTranslate["categories_" . $termId])) {
                    $this->createCategory($termId, $this->codeTo, $this->dataToTranslate["categories_" . $termId]);
                }
            }
            $this->dataToTranslate["categories"] = $this->polylangManager->getTranslationCategories($this->originalPost->post_category, $this->codeTo);
            $this->translatedPostId = $this->polylangManager->getTranslationPost($this->originalPost->ID, $this->codeTo);
            $this->postMetas = get_post_meta($this->originalPost->ID);
            $translatedPost = $this->translatedPostId ? get_post($this->translatedPostId) : null;
            if (!$translatedPost) {
                $this->createPost();
            } else {
                $this->updatePost();
            }
            $this->handleDefaultMetaPosts();
            $this->handleYoast();
            $this->handleRankMath();
            $this->handleSEOPress();
            $this->handleElementor();
            $this->handleACF();
            if ($this->action->isFromQueue()) {
                $this->action->setAsDone();
            } else {
                $this->action->setAsArchived();
            }
            update_post_meta($this->translatedPostId, '_has_been_translated_by_tsm', "true");
            update_post_meta($this->translatedPostId, '_translated_by_tsm_from', $this->codeFrom);
            update_post_meta($this->translatedPostId, '_tsm_first_visit_after_translation', "true");
            $this->action->save();
        } catch (Exception $e) {
            $this->action->setAsError()->setResponse(["error" => $e->getMessage()])->save();
        }
    }

    private function handleAssetsTranslations()
    {
        $content = $this->dataToTranslate["content"];
        foreach ($this->dataToTranslate as $key => $value) {
            if (!strstr($key, "src-")) {
                continue;
            }
            $originalUrlAsset = explode("src-", $key)[1];
            $newName = preg_replace('/ \d+x\d+/', '', $value);
            $urlAsset = preg_replace('/-\d+x\d+/', '', $originalUrlAsset);
            $mediaId = attachment_url_to_postid($urlAsset);
            if (!$mediaId) {
                continue;
            }
            $newMediaId = $this->duplicateMedia($mediaId, $newName);
            if ($newMediaId instanceof WP_Error || !$newMediaId) {
                continue;
            }
            $newUrl = $this->getSameSizedMedia($originalUrlAsset, $mediaId, $newMediaId);
            if (!$newUrl) {
                continue;
            }
            $content = str_replace($originalUrlAsset, $newUrl, $content);
            $content = str_replace(":" . $mediaId, ":" . $newMediaId, $content);
            $content = str_replace("-" . $mediaId, "-" . $newMediaId, $content);
        }
        $this->dataToTranslate["content"] = $content;
    }

    private function duplicateMedia($mediaId, $name)
    {
        if (!function_exists('wp_crop_image')) {
            include(ABSPATH . 'wp-admin/includes/image.php');
        }
        if (!function_exists('wp_get_current_user')) {
            include(ABSPATH . 'wp-includes/pluggable.php');
        }
        require_once(ABSPATH . 'wp-admin/includes/file.php');
        $media = get_post($mediaId);
        if (!$media) {
            return false;
        }
        $path = get_attached_file($mediaId);
        $explodedPath = explode("/", $path);
        $fileName = end($explodedPath);
        $name = str_replace("." . pathinfo($fileName, PATHINFO_EXTENSION), "", $name);
        $newFileName = sanitize_file_name($name) . "." . pathinfo($fileName, PATHINFO_EXTENSION);
        $newPath = str_replace($fileName, $newFileName, $path);
        if (file_exists($newPath)) {
            $newFileName = sanitize_file_name($name) . "-" . time() . "." . pathinfo($fileName, PATHINFO_EXTENSION);
            $newPath = str_replace($fileName, $newFileName, $path);
        }
        if (!copy($path, $newPath)) {
            return false;
        }
        $newGuid = str_replace($fileName, $newFileName, $media->guid);
        $newMedia = [
            "post_title" => basename($newFileName),
            "post_content" => '',
            "post_status" => "inherit",
            "post_mime_type" => $media->post_mime_type,
            "guid" => $newGuid,
            "post_type" => "attachment",
        ];
        $newMediaId = wp_insert_attachment(
            $newMedia,
            $newPath
        );
        if ($newMediaId instanceof WP_Error) {
            return $newMediaId;
        }
        $attachmentData = wp_generate_attachment_metadata($newMediaId, $newPath);
        wp_update_attachment_metadata($newMediaId, $attachmentData);
        return $newMediaId;
    }

    private function getSameSizedMedia($originalUrlAsset, $mediaId, $newMediaId)
    {
        $img_meta = wp_get_attachment_metadata($mediaId);
        $img_sizes = $img_meta['sizes'];

        if (!$img_sizes) {
            return false;
        }

        $size = null;
        foreach ($img_sizes as $key => $image) {
            if (strstr($originalUrlAsset, $image['file'])) {
                $size = $key;
                break;
            }
        }

        if (!$size) {
            return false;
        }

        return wp_get_attachment_image_src($newMediaId, $size)[0];
    }

    private function createCategory($originalCategoryId, $codeTo, $categoryNameTranslated)
    {
        $category = get_term($originalCategoryId, "category");
        $categoryTranslated = wp_insert_term($categoryNameTranslated, "category", [
            "slug" => sanitize_title($categoryNameTranslated),
            "description" => $category->description,
            "parent" => $category->parent
        ]);
        if (is_wp_error($categoryTranslated)) {
            return;
        }
        $allTranslationsTerms = $this->polylangManager->getAllTranslationsTerm($originalCategoryId);
        if (isset($allTranslationsTerms[$codeTo])) {
            $allTranslationsTerms[$codeTo]["termId"] = $categoryTranslated["term_id"];
        }
        $this->polylangManager->saveAllTranslationsTerms($allTranslationsTerms);
    }

    private function createPost()
    {
        global $tsm, $wpdb;
        $originalTranslations = $tsm->getPolylangManager()->getAllTranslationsPost($this->originalPost->ID);
        $translations = [];

        foreach ($originalTranslations as $slug => $translation) {
            if ($translation["postId"]) {
                $translations[$slug] = $translation["postId"];
            }
        }

        $postName = isset($this->dataToTranslate["slug"]) ? $this->dataToTranslate["slug"] : $this->originalPost->post_name . "-" . $this->codeTo;
        $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $postName);
        $exists = $wpdb->get_var($query);
        if (!empty($exists)) {
            $postName .= "-" . time();
        }

        $postData = [
            'ID' => $this->translatedPostId,
            'post_name' => $postName,
            'post_status' => "draft",
            'post_type' => $this->originalPost->post_type,
            'post_author' => $this->originalPost->post_author,
            'post_title' => isset($this->dataToTranslate["title"]) ? $this->dataToTranslate["title"] : $this->originalPost->post_title,
            'post_content' => isset($this->dataToTranslate["content"]) ? $this->dataToTranslate["content"] : $this->originalPost->post_content,
        ];
        if (isset($this->dataToTranslate["categories"])) {
            $postData['post_category'] = $this->dataToTranslate["categories"];
        }
        if (isset($this->dataToTranslate["excerpt"])) {
            $postData["post_excerpt"] = $this->dataToTranslate["excerpt"];
        }
        $translations[$this->action->getSlugTo()] = wp_insert_post($postData, true);
        $this->translatedPostId = $translations[$this->action->getSlugTo()];
        $tsm->getPolylangManager()->saveAllTranslationsPost($translations);
    }

    private function updatePost()
    {
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

    private function handleDefaultMetaPosts()
    {
        foreach ($this->postMetas as $key => $value) {
            $valueKey = $value[0];
            if ($this->is_json($valueKey)) {
                $valueKey = wp_slash($valueKey);
            } else if ($this->is_serialized($valueKey)) {
                $valueKey = unserialize($valueKey);
            }
            $valueKey = $this->replaceValue($valueKey);
            update_post_meta($this->translatedPostId, $key, $valueKey);
        }
    }

    private function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    private function is_serialized($string)
    {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }

    private function replaceValue($value)
    {
        if (is_array($value)) {
            foreach ($value as $key => $val) {
                $value[$key] = $this->replaceValue($val);
            }
        } else if (is_string($value)) {
            $value = $this->linkManager->translateInternalLinks($value, $this->codeFrom, $this->codeTo);
            $value = str_replace($this->originalPost->ID, $this->translatedPostId, $value);
        }
        return $value;
    }

    private function handleYoast()
    {
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
            if (isset($this->dataToTranslate["yoastFocusKeyword"])) {
                update_post_meta($this->translatedPostId, "_yoast_wpseo_focuskw", $this->dataToTranslate["yoastFocusKeyword"]);
            }
        }
    }

    private function handleRankMath()
    {
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

    private function handleSEOPress()
    {
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

    private function handleElementor()
    {
        if (is_plugin_active("elementor/elementor.php")) {
            foreach ($this->postMetas as $key => $value) {
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

    private function handleACF()
    {
        if (is_plugin_active("advanced-custom-fields/acf.php")) {
            foreach ($this->dataToTranslate as $key => $value) {
                if (strstr($key, "acf_")) {
                    $key = substr($key, 4);
                    if (isset($this->postMetas[$key]) && isset($this->postMetas["_" . $key])) {
                        if ($this->is_json($value)) {
                            $value = wp_slash($value);
                        }
                        if ($this->is_serialized($value)) {
                            $value = unserialize($value);
                        }

                        update_post_meta($this->translatedPostId, $key, $value);
                        update_post_meta($this->translatedPostId, "_" . $key, $this->postMetas["_" . $key][0]);
                    }
                }
            }
        }
    }

    public function isSuccess()
    {
        return $this->action->getState() === DAOActions::$STATE["DONE"] || $this->action->getState() === DAOActions::$STATE["ARCHIVED"];
    }

    public function getResponse()
    {
        return $this->action->getResponse();
    }
}