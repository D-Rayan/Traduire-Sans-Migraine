<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Create
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action("tsm-woocommerce-product-translate", [$this, "translateProduct"], 10, 4);
    }


    public function translateProduct($originalProductId, $translatedProductId, $data, $slugLanguageDestination)
    {
        global $wpdb;

        if (get_post_type($originalProductId) !== "product" && get_post_type($originalProductId) !== "product_variation") {
            return;
        }

        $languageOriginal = LanguagePost::getLanguage($originalProductId);
        if (!$languageOriginal) {
            return;
        }

        $categories = wc_get_product_category_list($originalProductId);
        $postMetas = get_post_meta($originalProductId);
        $tags = get_the_terms($originalProductId, "product_tag");

        $productMetaLookup = $wpdb->get_row("SELECT * FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id = $originalProductId");
        $productAttributesLookup = $wpdb->get_results("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = $originalProductId OR product_or_parent_id = $originalProductId");

        $this->createPostMeta($translatedProductId, $postMetas, $data, $originalProductId);
        $this->createPostMetaLookup($translatedProductId, $productMetaLookup, $slugLanguageDestination, $data);
        $this->createPostAttributesLookup($translatedProductId, $productAttributesLookup, $data, $slugLanguageDestination, $languageOriginal["code"]);
        $this->assignProductToCategories($translatedProductId, $categories, $slugLanguageDestination, $data);
        $this->assignTagsToProduct($translatedProductId, $tags, $slugLanguageDestination, $data);


        $postStatus = get_post_field("post_status", $translatedProductId);
        $postsChildren = $wpdb->get_results("SELECT * FROM $wpdb->posts WHERE post_parent = $originalProductId AND post_type NOT IN ('revision', 'attachment', 'auto-draft')");
        foreach ($postsChildren as $postChild) {
            $childrenPostCreated = wp_insert_post([
                "post_title" => isset($data["child_post_title_" . $postChild->ID]) ? $data["child_post_title_" . $postChild->ID] : $postChild->post_title . " - " . $slugLanguageDestination,
                "post_content" => isset($data["child_post_content_" . $postChild->ID]) ? $data["child_post_content_" . $postChild->ID] : "",
                "post_status" => $postStatus,
                "post_type" => $postChild->post_type,
                "post_author" => $postChild->post_author,
                "menu_order" => $postChild->menu_order,
                "post_parent" => $translatedProductId
            ]);
            if ($childrenPostCreated) {
                do_action("tsm-woocommerce-product-translate", $postChild->ID, $childrenPostCreated, $data, $slugLanguageDestination);
            }
        }
    }

    private function createPostMeta($newPostId, $postMetas, $data, $oldPostId)
    {
        $traduire_sans_migraine_inventory_linked_find = false;
        $currentPostMeta = get_post_meta($newPostId);
        foreach ($postMetas as $metaKey => $values) {
            if ($metaKey === "traduire_sans_migraine_inventory_linked") {
                $traduire_sans_migraine_inventory_linked_find = true;
            }
            if (isset($currentPostMeta[$metaKey])) {
                continue;
            }
            $metaValue = $values[0];
            update_post_meta($newPostId, $metaKey, $metaValue);
        }
        if (!$traduire_sans_migraine_inventory_linked_find) {
            update_post_meta($newPostId, "traduire_sans_migraine_inventory_linked", 1);
            update_post_meta($oldPostId, "traduire_sans_migraine_inventory_linked", 1);
        }
    }

    private function createPostMetaLookup($newPostId, $productMetaLookup, $intoLanguageCode, $data)
    {
        global $wpdb;

        $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_meta_lookup WHERE product_id=$newPostId");
        if ($count && $count > 0) {
            return;
        }
        $wpdb->insert($wpdb->prefix . "wc_product_meta_lookup", [
            "product_id" => $newPostId,
            "sku" => $productMetaLookup->sku . "-" . $intoLanguageCode,
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

    private function createPostAttributesLookup($newPostId, $productAttributesLookup, $data, $slugLanguageDestination, $slugLanguageOriginal)
    {
        global $wpdb;

        $attributesName = [];
        foreach ($productAttributesLookup as $productAttributeLookup) {
            $count = $wpdb->get_var("SELECT COUNT(*) FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE 
                                                           product_id=$newPostId AND 
                                                           term_id={$productAttributeLookup->term_id} AND
                                                           taxonomy='{$productAttributeLookup->taxonomy}'");
            if ($count && $count > 0) {
                return;
            }

            if (isset($data["term_" . $productAttributeLookup->term_id])) {
                $originalTerm = get_term($productAttributeLookup->term_id);
                if (!$originalTerm) {
                    continue;
                }
                $term = $data["term_" . $productAttributeLookup->term_id];
                $slug = sanitize_title($term);
                // check if slug available
                $slug = wp_unique_term_slug($slug, (object)["slug" => $slug, "term_id" => 0, "term_group" => 0, "taxonomy" => $originalTerm->taxonomy]);
                $wpdb->insert($wpdb->terms, [
                    "name" => $term,
                    "slug" => $slug,
                    "term_group" => 0
                ]);
                $newTermId = $wpdb->insert_id;
                $wpdb->insert($wpdb->term_taxonomy, [
                    "term_id" => $newTermId,
                    "taxonomy" => $originalTerm->taxonomy,
                    "description" => $originalTerm->description,
                    "parent" => 0,
                    "count" => 0
                ]);
                $translations = TranslationTerms::findTranslationFor($originalTerm->term_id);
                $translations->addTranslation($slugLanguageDestination, $newTermId);
                $translations->addTranslation($slugLanguageOriginal, $productAttributeLookup->term_id);

                $productAttributeLookup->term_id = $newTermId;
            }

            if ($productAttributeLookup->product_or_parent_id === $productAttributeLookup->product_id) {
                $productAttributeLookup->product_or_parent_id = $newPostId;
            } else if (!empty($productAttributeLookup->product_or_parent_id)) {
                $translations = TranslationPost::findTranslationFor($productAttributeLookup->product_or_parent_id);
                if ($translations->getTranslation($slugLanguageDestination)) {
                    $productAttributeLookup->product_or_parent_id = $translations->getTranslation($slugLanguageDestination);
                }
            }
            $translations = TranslationTerms::findTranslationFor($productAttributeLookup->term_id);
            $productAttributeLookup->term_id = $translations->getTranslation($slugLanguageDestination);
            if (empty($productAttributeLookup->term_id)) {
                continue;
            }
            $attributeName = str_replace("pa_", "", $productAttributeLookup->taxonomy);
            $attributesName[$attributeName] = true;
            $wpdb->insert($wpdb->prefix . "wc_product_attributes_lookup", [
                "product_id" => $newPostId,
                "product_or_parent_id" => $productAttributeLookup->product_or_parent_id,
                "taxonomy" => $productAttributeLookup->taxonomy,
                "term_id" => $productAttributeLookup->term_id,
                "is_variation_attribute" => $productAttributeLookup->is_variation_attribute,
                "in_stock" => $productAttributeLookup->in_stock,
            ]);
        }
        foreach ($attributesName as $attributeName => $value) {
            $this->handleAttributes($attributeName, $data, $slugLanguageDestination);
        }
    }

    private function handleAttributes($attributeName, $data, $slugLanguageDestination)
    {
        global $wpdb;

        $id = $wpdb->get_var($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='%s'", $attributeName));
        if (!$id) {
            return;
        }
        $translations = TranslationAttribute::findTranslationFor($id);
        if (!empty($translations->getTranslation($slugLanguageDestination))) {
            return;
        }
        if (isset($data["attribute_" . $id])) {
            $attribute = $data["attribute_" . $id];
            $translations->addTranslation($slugLanguageDestination, $attribute);
            $translations->save();
        }
    }

    private function assignProductToCategories($newPostId, $categories, $intoLanguageCode, $data)
    {
        $categories = explode(",", $categories);
        foreach ($categories as $category) {
            $term = get_term_by("name", $category, "product_cat");
            if (!$term) {
                continue;
            }
            $translations = TranslationTerms::findTranslationFor($term->term_id);
            $translatedCategory = $translations->getTranslation($intoLanguageCode);
            if (!$translatedCategory) {
                do_action("tsm-woocommerce-category-translate", $term->term_id, $data, $intoLanguageCode);
                $translations = TranslationTerms::findTranslationFor($term->term_id);
                $translatedCategory = $translations->getTranslation($intoLanguageCode);
            }
            if (!$translatedCategory) {
                continue;
            }
            wp_set_post_terms($newPostId, $translatedCategory, "product_cat", true);
        }
    }

    private function assignTagsToProduct($newPostId, $tags, $intoLanguageCode, $data)
    {
        if (!is_array($tags)) {
            return;
        }
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            $translatedTag = $translations->getTranslation($intoLanguageCode);
            if (!$translatedTag) {
                do_action("tsm-woocommerce-category-translate", $tag->term_id, $data, $intoLanguageCode);
                $translations = TranslationTerms::findTranslationFor($tag->term_id);
                $translatedTag = $translations->getTranslation($intoLanguageCode);
            }
            if (!$translatedTag) {
                continue;
            }
            wp_set_post_terms($newPostId, $translatedTag, "product_tag", true);
        }
    }
}

$Create = new Create();
$Create->init();