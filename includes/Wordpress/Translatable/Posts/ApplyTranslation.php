<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts;


use Elementor\Core\Files\CSS\Post as Post_CSS;
use Elementor\Plugin;
use Exception;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractApplyTranslation;
use WP_Error;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends AbstractApplyTranslation
{
    protected $postMetas;
    protected $translatedPostId;

    public function __construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        global $tsm;
        if (!is_object($this->action)) {
            return;
        }
        $countAssetsCreated = 0;
        if (isset($this->dataToTranslate["content"])) {
            $this->dataToTranslate["content"] = $tsm->getLinkManager()->translateInternalLinks($this->dataToTranslate["content"], $this->codeFrom, $this->codeTo);
            $countAssetsCreated = $this->handleAssetsTranslations();
        }
        $countCategoriesCreated = 0;

        $childrenActions = $this->action->getChildren();
        foreach ($childrenActions as $childAction) {
            if ($childAction->getActionType() === DAOActions::$ACTION_TYPE["TERMS"]) {
                $countCategoriesCreated++;
            }
        }
        $this->dataToTranslate["categories"] = [];
        foreach ($this->originalObject->post_category as $categoryId) {
            $translations = TranslationTerms::findTranslationFor($categoryId);
            $translatedCategory = $translations->getTranslation($this->codeTo);
            if (!$translatedCategory) {
                continue;
            }
            $this->dataToTranslate["categories"][] = $translatedCategory;
        }

        $translations = TranslationPost::findTranslationFor($this->originalObject->ID);
        $this->translatedPostId = $translations->getTranslation($this->codeTo);
        $this->postMetas = get_post_meta($this->originalObject->ID);
        $translatedPost = $this->translatedPostId ? get_post($this->translatedPostId) : null;
        if (!$translatedPost) {
            $this->createPost();
        } else {
            $this->updatePost();
        }
        LanguagePost::setLanguage($this->translatedPostId, $this->codeTo);
        $originalTranslations = TranslationPost::findTranslationFor($this->originalObject->ID);
        $originalTranslations
            ->addTranslation($this->codeTo, $this->translatedPostId)
            ->addTranslation($this->codeFrom, $this->originalObject->ID)
            ->save();

        $this->handleDefaultMetaPosts();
        $yoastTranslated = $this->handleYoast();
        $rankMathTranslated = $this->handleRankMath();
        $this->handleSEOPress();
        $this->handleElementor();
        $this->handleACF();
        update_post_meta($this->translatedPostId, '_has_been_translated_by_tsm', "true");
        update_post_meta($this->translatedPostId, '_translated_by_tsm_from', $this->codeFrom);
        update_post_meta($this->translatedPostId, '_tsm_first_visit_after_translation', "true");
        update_post_meta($this->translatedPostId, '_summary_translated_by_tsm', [
            "linksTranslated" => $tsm->getLinkManager()->getLinksTranslatedCount(),
            "categoriesTranslated" => $countCategoriesCreated,
            "slugTranslated" => isset($this->dataToTranslate["slug"]) && $this->dataToTranslate["slug"] !== $this->originalObject->post_name,
            "assetsTranslated" => $countAssetsCreated,
            "yoastTranslated" => $yoastTranslated,
            "rankMathTranslated" => $rankMathTranslated
        ]);
    }

    private function handleAssetsTranslations()
    {
        $countAssetsCreated = 0;
        $content = $this->dataToTranslate["content"];
        $currentDomain = get_site_url();
        foreach ($this->dataToTranslate as $key => $value) {
            try {
                if (!strstr($key, "src-")) {
                    continue;
                }
                $originalUrlAsset = explode("src-", $key)[1];
                if (strpos($originalUrlAsset, $currentDomain) !== 0) {
                    continue;
                }
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
                $countAssetsCreated++;
            } catch (Exception $e) {
                tsm_log($e->getMessage());
                continue;
            }
        }
        $this->dataToTranslate["content"] = $content;
        return $countAssetsCreated;
    }

    private function duplicateMedia($mediaId, $name)
    {
        try {
            if (!function_exists('wp_crop_image')) {
                include(ABSPATH . 'wp-admin/includes/image.php');
            }
            if (!function_exists('wp_get_current_user')) {
                include(ABSPATH . 'wp-includes/pluggable.php');
            }
            if (!function_exists('get_file_description')) {
                include(ABSPATH . 'wp-admin/includes/file.php');
            }
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
        } catch (Exception $e) {
            tsm_log($e->getMessage());
            return false;
        }
    }

    private function getSameSizedMedia($originalUrlAsset, $mediaId, $newMediaId)
    {
        try {
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
        } catch (Exception $e) {
            tsm_log($e->getMessage());
            return false;
        }
    }

    private function createPost()
    {
        global $wpdb;

        $postName = isset($this->dataToTranslate["slug"]) ? str_replace(" ", "-", $this->dataToTranslate["slug"]) : $this->originalObject->post_name . "-" . $this->codeTo;
        $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name LIKE %s', $postName);
        $exists = $wpdb->get_var($query);
        if (!empty($exists)) {
            $postName .= "-" . time();
        }

        $postData = [
            'ID' => $this->translatedPostId,
            'post_name' => $postName,
            'post_status' => "draft",
            'post_type' => $this->originalObject->post_type,
            'post_author' => $this->originalObject->post_author,
            'post_title' => isset($this->dataToTranslate["title"]) ? $this->dataToTranslate["title"] : $this->originalObject->post_title,
            'post_content' => isset($this->dataToTranslate["content"]) ? $this->dataToTranslate["content"] : $this->originalObject->post_content,
        ];
        if (isset($this->dataToTranslate["categories"])) {
            $postData['post_category'] = $this->dataToTranslate["categories"];
        }
        if (isset($this->dataToTranslate["excerpt"])) {
            $postData["post_excerpt"] = $this->dataToTranslate["excerpt"];
        }
        $postId = wp_insert_post($postData, true);
        if ($postId instanceof WP_Error) {
            throw new Exception($postId->get_error_message());
        }
        $this->translatedPostId = $postId;
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

    protected function handleDefaultMetaPosts()
    {
        foreach ($this->postMetas as $key => $value) {
            try {
                $valueKey = $value[0];
                if ($this->is_json($valueKey)) {
                    $valueKey = wp_slash($valueKey);
                } else if ($this->is_serialized($valueKey)) {
                    $valueKey = unserialize($valueKey);
                }
                $valueKey = $this->replaceInternalLinksAndIds($valueKey);
                update_post_meta($this->translatedPostId, $key, $valueKey);
            } catch (Exception $e) {
                tsm_log($e->getMessage());
                continue;
            }
        }
    }

    protected function replaceInternalLinksAndIds($value)
    {
        try {
            global $tsm;
            if (is_array($value)) {
                foreach ($value as $key => $val) {
                    $value[$key] = $this->replaceInternalLinksAndIds($val);
                }
            } else if (is_string($value)) {
                $value = $tsm->getLinkManager()->translateInternalLinks($value, $this->codeFrom, $this->codeTo);
                $value = str_replace($this->originalObject->ID, $this->translatedPostId, $value);
            }
            return $value;
        } catch (Exception $e) {
            tsm_log($e->getMessage());
            return $value;
        }
    }

    private function handleYoast()
    {
        $translated = false;
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            try {
                if (isset($this->dataToTranslate["metaTitle"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "_yoast_wpseo_title", $this->dataToTranslate["metaTitle"]);
                }
                if (isset($this->dataToTranslate["metaDescription"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "_yoast_wpseo_metadesc", $this->dataToTranslate["metaDescription"]);
                }
                if (isset($this->dataToTranslate["metaKeywords"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "_yoast_wpseo_metakeywords", $this->dataToTranslate["metaKeywords"]);
                }
                if (isset($this->dataToTranslate["yoastFocusKeyword"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "_yoast_wpseo_focuskw", $this->dataToTranslate["yoastFocusKeyword"]);
                }
            } catch (Exception $e) {
                tsm_log($e->getMessage());
                return false;
            }
        }
        return $translated;
    }

    private function handleRankMath()
    {
        $translated = false;
        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            try {
                if (isset($this->dataToTranslate["rankMathDescription"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "rank_math_description", $this->dataToTranslate["rankMathDescription"]);
                }
                if (isset($this->dataToTranslate["rankMathTitle"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "rank_math_title", $this->dataToTranslate["rankMathTitle"]);
                }
                if (isset($this->dataToTranslate["rankMathFocusKeyword"])) {
                    $translated = true;
                    update_post_meta($this->translatedPostId, "rank_math_focus_keyword", $this->dataToTranslate["rankMathFocusKeyword"]);
                }
            } catch (Exception $e) {
                tsm_log($e->getMessage());
                return false;
            }
        }
        return $translated;
    }

    private function handleSEOPress()
    {
        if (is_plugin_active("wp-seopress/seopress.php")) {
            try {
                if (isset($this->dataToTranslate["seopress_titles_desc"])) {
                    update_post_meta($this->translatedPostId, "seopress_titles_desc", $this->dataToTranslate["seopress_titles_desc"]);
                }
                if (isset($this->dataToTranslate["seopress_titles_title"])) {
                    update_post_meta($this->translatedPostId, "seopress_titles_title", $this->dataToTranslate["seopress_titles_title"]);
                }
                if (isset($this->dataToTranslate["seopress_analysis_target_kw"])) {
                    update_post_meta($this->translatedPostId, "seopress_analysis_target_kw", $this->dataToTranslate["seopress_analysis_target_kw"]);
                }
            } catch (Exception $e) {
                tsm_log($e->getMessage());
                return false;
            }
        }
    }

    private function handleElementor()
    {
        global $tsm;
        if (is_plugin_active("elementor/elementor.php")) {
            $hasMetaElementor = false;
            $hasDataTranslatedElementor = false;
            foreach ($this->dataToTranslate as $key => $value) {
                if (!$hasDataTranslatedElementor) {
                    $hasDataTranslatedElementor = strstr($key, "elementor");
                }
            }
            if ($hasDataTranslatedElementor === false) {
                return;
            }
            foreach ($this->postMetas as $key => $value) {
                try {
                    if (strstr($key, "elementor")) {
                        $valueKey = isset($this->dataToTranslate[$key]) ? $this->dataToTranslate[$key] : $value[0];
                        $valueKey = $tsm->getLinkManager()->translateInternalLinks($valueKey, $this->codeFrom, $this->codeTo);
                        if ($this->is_json($valueKey)) {
                            $valueKey = wp_slash($valueKey);
                        }
                        if ($this->is_serialized($valueKey)) {
                            $valueKey = unserialize($valueKey);
                        }
                        $hasMetaElementor = true;
                        update_post_meta($this->translatedPostId, $key, $valueKey);
                    }
                } catch (Exception $e) {
                    tsm_log($e->getMessage());
                    continue;
                }
            }
            if ($hasMetaElementor) {
                try {
                    $document = Plugin::$instance->documents->get($this->translatedPostId, false);
                    if ($document) {
                        $document->save_template_type();
                        $document->save_version();
                        // Remove Post CSS
                        $post_css = Post_CSS::create($this->translatedPostId);
                        $post_css->delete();
                        $document->save($document->get_elements_data());
                    }
                } catch (Exception $e) {
                    tsm_log($e->getMessage());
                }
                try {
                    if (Plugin::$instance && Plugin::$instance->posts) {
                        Plugin::$instance->posts->save_post($this->translatedPostId);
                    }
                } catch (Exception $e) {
                    tsm_log($e->getMessage());
                }
            }
        }
    }

    private function handleACF()
    {
        if (is_plugin_active("advanced-custom-fields/acf.php")) {
            foreach ($this->dataToTranslate as $key => $value) {
                try {
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
                } catch (Exception $e) {
                    tsm_log($e->getMessage());
                    continue;
                }
            }
        }
    }

    protected function getCodeFrom()
    {
        $language = LanguagePost::getLanguage($this->originalObject->ID);
        if (empty($language)) {
            throw new Exception("Language not found");
        }
        return $language["code"];
    }

    protected function getTranslatedId()
    {
        return $this->translatedPostId;
    }
}
