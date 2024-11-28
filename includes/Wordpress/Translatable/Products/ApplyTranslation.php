<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Products;


use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends Translatable\Posts\ApplyTranslation
{
    private $isNewProduct;

    public function __construct($action, $translationData)
    {
        $this->isNewProduct = false;
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        $translations = TranslationPost::findTranslationFor($this->originalObject->ID);
        $this->isNewProduct = empty($translations->getTranslation($this->codeTo));
        parent::processTranslation();
        if (!$this->translatedPostId) {
            return;
        }
        $this->translateProduct();
    }

    public function translateProduct()
    {
        global $wpdb;

        $languageOriginal = LanguagePost::getLanguage($this->originalObject->ID);
        if (!$languageOriginal) {
            return;
        }


        $this->handleProduct($this->originalObject->ID, $this->translatedPostId, $this->isNewProduct);
        $postStatus = get_post_field("post_status", $this->translatedPostId);
        $postsChildren = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type NOT IN ('revision', 'attachment', 'auto-draft')", $this->originalObject->ID));
        foreach ($postsChildren as $postChild) {
            $isNewProductChild = false;
            $translations = TranslationPost::findTranslationFor($postChild->ID);
            $translationId = $translations->getTranslation($this->codeTo);
            if (empty($translationId)) {
                $isNewProductChild = true;
                $childrenPostCreated = wp_insert_post([
                    "post_title" => isset($this->dataToTranslate["child_post_title_" . $postChild->ID]) ? $this->dataToTranslate["child_post_title_" . $postChild->ID] : $postChild->post_title . " - " . $this->codeTo,
                    "post_content" => isset($this->dataToTranslate["child_post_content_" . $postChild->ID]) ? $this->dataToTranslate["child_post_content_" . $postChild->ID] : "",
                    "post_status" => $postStatus,
                    "post_type" => $postChild->post_type,
                    "post_author" => $postChild->post_author,
                    "menu_order" => $postChild->menu_order,
                    "post_parent" => $this->translatedPostId
                ]);
                if (!$childrenPostCreated) {
                    continue;
                }
                LanguagePost::setLanguage($childrenPostCreated, $this->codeTo);
                $translations
                    ->addTranslation($this->codeTo, $childrenPostCreated)
                    ->addTranslation($languageOriginal["code"], $postChild->ID)
                    ->save();
                $translationId = $childrenPostCreated;
            }
            $this->handleProduct($postChild->ID, $translationId, $isNewProductChild);
        }
    }

    private function handleProduct($originalId, $newId, $isNewProduct)
    {
        if ($isNewProduct) {
            $this->createPostMeta($originalId, $newId);
            $this->createPostMetaLookup($originalId, $newId);
            $this->createPostAttributesLookup($originalId, $newId);
        }
        $this->assignProductToCategories($originalId, $newId);
        $this->assignTagsToProduct($originalId, $newId);
    }

    private function createPostMeta($originalId, $newId)
    {
        $postMetas = get_post_meta($originalId);
        $traduire_sans_migraine_inventory_linked_find = false;
        $currentPostMeta = get_post_meta($newId);
        foreach ($postMetas as $metaKey => $values) {
            if ($metaKey === "traduire_sans_migraine_inventory_linked") {
                $traduire_sans_migraine_inventory_linked_find = true;
            }
            if (isset($currentPostMeta[$metaKey])) {
                continue;
            }
            $metaValue = $values[0];
            update_post_meta($newId, $metaKey, $metaValue);
        }
        if (!$traduire_sans_migraine_inventory_linked_find) {
            update_post_meta($newId, "traduire_sans_migraine_inventory_linked", 1);
            update_post_meta($originalId, "traduire_sans_migraine_inventory_linked", 1);
        }
    }

    private function createPostMetaLookup($originalId, $newId)
    {
        global $wpdb;
        $productMetaLookup = $wpdb->get_row($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = %d", $originalId));
        $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id= %d", $newId));
        if ($count && $count > 0) {
            return;
        }
        $wpdb->insert($wpdb->prefix . "wc_product_meta_lookup", [
            "product_id" => $newId,
            "sku" => $productMetaLookup->sku . "-" . $this->codeTo,
            "virtual" => $productMetaLookup->virtual,
            "downloadable" => $productMetaLookup->downloadable,
            "min_price" => $productMetaLookup->min_price,
            "max_price" => $productMetaLookup->max_price,
            "onsale" => $productMetaLookup->onsale,
            "stock_quantity" => $productMetaLookup->stock_quantity,
            "stock_status" => $productMetaLookup->stock_status,
            "rating_count" => 0,
            "average_rating" => 0,
            "total_sales" => 0,
            "tax_status" => $productMetaLookup->tax_status,
            "tax_class" => $productMetaLookup->tax_class,
        ]);
    }

    private function createPostAttributesLookup($originalId, $newId)
    {
        global $wpdb;
        $productAttributesLookup = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d OR product_or_parent_id = %d", $originalId, $originalId));
        foreach ($productAttributesLookup as $productAttributeLookup) {
            $count = $wpdb->get_var($wpdb->prepare("SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE 
                                                           product_id=%d AND 
                                                           term_id=%d AND
                                                           taxonomy=%s", $newId, $productAttributeLookup->term_id, $productAttributeLookup->taxonomy));
            if ($count && $count > 0) {
                return;
            }

            if ($productAttributeLookup->product_or_parent_id === $productAttributeLookup->product_id) {
                $productAttributeLookup->product_or_parent_id = $newId;
            } else if (!empty($productAttributeLookup->product_or_parent_id)) {
                $translations = TranslationPost::findTranslationFor($productAttributeLookup->product_or_parent_id);
                if ($translations->getTranslation($this->codeTo)) {
                    $productAttributeLookup->product_or_parent_id = $translations->getTranslation($this->codeTo);
                }
            }
            $translations = TranslationTerms::findTranslationFor($productAttributeLookup->term_id);
            $productAttributeLookup->term_id = $translations->getTranslation($this->codeTo);
            if (empty($productAttributeLookup->term_id)) {
                continue;
            }
            $wpdb->insert($wpdb->prefix . "wc_product_attributes_lookup", [
                "product_id" => $newId,
                "product_or_parent_id" => $productAttributeLookup->product_or_parent_id,
                "taxonomy" => $productAttributeLookup->taxonomy,
                "term_id" => $productAttributeLookup->term_id,
                "is_variation_attribute" => $productAttributeLookup->is_variation_attribute,
                "in_stock" => $productAttributeLookup->in_stock,
            ]);
        }
    }

    private function assignProductToCategories($originalId, $newId)
    {
        $categories = get_the_terms($originalId, "product_cat");
        if (!is_array($categories)) {
            return;
        }
        foreach ($categories as $term) {
            $translations = TranslationTerms::findTranslationFor($term->term_id);
            $translatedCategory = $translations->getTranslation($this->codeTo);
            if (!$translatedCategory) {
                continue;
            }
            wp_set_post_terms($newId, $translatedCategory, "product_cat", true);
        }
    }

    private function assignTagsToProduct($originalId, $newId)
    {
        $tags = get_the_terms($originalId, "product_tag");
        if (!is_array($tags)) {
            return;
        }
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            $translatedTag = $translations->getTranslation($this->codeTo);
            if (!$translatedTag) {
                continue;
            }
            wp_set_post_terms($$newId, $translatedTag, "product_tag", true);
        }
    }
}