<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable\Posts\Action;

if (!defined("ABSPATH")) {
    exit;
}

class GetPost
{
    public function __construct()
    {
    }

    public function init()
    {
        add_action("wp_ajax_traduire-sans-migraine_get_post", [$this, "getPost"]);
    }

    public function getPost()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["postId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $post = get_post($_GET["postId"], ARRAY_A);
        if (empty($post)) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 404);
            wp_die();
        }
        $language = LanguagePost::getLanguage($post["ID"]);
        if (empty($language)) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 404);
            wp_die();
        }
        $post["translations"] = [];
        $post["currentSlug"] = $language["code"];
        $translations = TranslationPost::findTranslationFor($post["ID"]);
        foreach ($translations->getTranslations() as $slug => $translatedPostId) {
            $post["translations"][$slug] = $this->getTranslationPostData($post, $translatedPostId, $slug);
        }
        foreach ($tsm->getPolylangManager()->getLanguagesActives() as $slug => $languageActive) {
            if (isset($post["translations"][$slug])) {
                continue;
            }
            $post["translations"][$slug] = $this->getTranslationPostData($post, null, $slug);
        }
        wp_send_json_success([
            "post" => $post
        ]);
        wp_die();
    }

    private function getTranslationPostData($post, $translatedPostId, $slug)
    {
        global $tsm, $wpdb;
        $postExists = $translatedPostId && get_post_status($translatedPostId) !== "trash";
        $issuesTranslatedUrls = $tsm->getLinkManager()->getIssuedInternalLinks($post["post_content"], $post["currentSlug"], $slug);
        $notTranslated = $issuesTranslatedUrls["notTranslated"];
        $notPublished = $issuesTranslatedUrls["notPublished"];
        $missingCategories = $this->getMissingCategories($post, $slug);
        $temporaryAction = new Action([
            "objectId" => $post["ID"],
            "slugTo" => $slug,
            "origin" => "HOOK"
        ]);
        $estimatedQuota = $temporaryAction->getEstimatedQuota();
        $preparedData = $temporaryAction->getDataToTranslate();
        $attributes = [];
        $terms = [];
        foreach ($preparedData as $key => $value) {
            if (strstr($key, "term_")) {
                $termId = str_replace("term_", "", $key);
                $term = get_term($termId);
                if ($term) {
                    $terms[] = $term->name;
                }
            } else if (strstr($key, "attribute_")) {
                $attributeId = str_replace("attribute_", "", $key);
                $attributeName = $wpdb->get_var($wpdb->prepare("SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_id=%d", $attributeId));
                if (!empty($attributeName)) {
                    $attributes[] = $attributeName;
                }
            }
        }
        return [
            "name" => $postExists ? get_post_field("post_title", $translatedPostId) : "",
            "code" => $slug,
            "postId" => $postExists ? $translatedPostId : null,
            "issues" => [
                "urlNotTranslated" => $notTranslated,
                "urlNotPublished" => $notPublished,
                "categoriesNotTranslated" => $missingCategories,
                "attributesNotTranslated" => $attributes,
                "termsNotTranslated" => $terms
            ],
            "estimatedQuota" => $estimatedQuota,
        ];
    }

    private function getMissingCategories($post, $slugLangDestination)
    {
        global $tsm;
        $missingCategories = [];
        $isProduct = $post["post_type"] === "product";
        $categories = get_the_terms($post["ID"], ($isProduct) ? "product_cat" : "category");
        if (empty($categories) || !is_array($categories)) {
            return [];
        }
        foreach ($categories as $category) {
            $translations = TranslationTerms::findTranslationFor($category->term_id);
            $translatedCategory = $translations->getTranslation($slugLangDestination);
            if (is_numeric($category->parent) && $category->parent > 0) {
                $categories[] = get_term($category->parent, $category->taxonomy);
            }
            if (empty($translatedCategory)) {
                $missingCategories[$category->term_id] = $category->name;
            }
        }
        $missingCategoriesWithoutKey = [];
        foreach ($missingCategories as $name) {
            $missingCategoriesWithoutKey[] = $name;
        }
        return $missingCategoriesWithoutKey;
    }
}

$GetPost = new GetPost();
$GetPost->init();