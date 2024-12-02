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

        $this->handleProduct($this->originalObject->ID, $this->translatedPostId, $this->isNewProduct);
        $postsChildren = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type NOT IN ('revision', 'attachment', 'auto-draft')", $this->originalObject->ID));
        foreach ($postsChildren as $postChild) {
            $isNewProductChild = false;
            $translations = TranslationPost::findTranslationFor($postChild->ID);
            $translationId = $translations->getTranslation($this->codeTo);
            if (empty($translationId)) {
                $isNewProductChild = true;
                $dateSQL = date("Y-m-d H:i:s");
                $dateGMTSQL = get_gmt_from_date($dateSQL);
                $postTitle = isset($this->dataToTranslate["child_post_title_" . $postChild->ID]) ? $this->dataToTranslate["child_post_title_" . $postChild->ID] : $postChild->post_title . " - " . $this->codeTo;
                $childrenPostCreated = wp_insert_post([
                    "post_title" => $postTitle,
                    "post_content" => isset($this->dataToTranslate["child_post_content_" . $postChild->ID]) ? $this->dataToTranslate["child_post_content_" . $postChild->ID] : "",
                    "post_status" => "publish",
                    "post_name" => sanitize_title($postTitle),
                    "post_type" => $postChild->post_type,
                    "post_author" => $postChild->post_author,
                    "menu_order" => $postChild->menu_order,
                    "post_parent" => $this->translatedPostId,
                    "post_date" => $dateSQL,
                    "post_date_gmt" => $dateGMTSQL,
                    "post_modified" => $dateSQL,
                    "post_modified_gmt" => $dateGMTSQL,
                ]);
                if (!$childrenPostCreated) {
                    continue;
                }
                LanguagePost::setLanguage($childrenPostCreated, $this->codeTo);
                $translations
                    ->addTranslation($this->codeTo, $childrenPostCreated)
                    ->addTranslation($this->codeFrom, $postChild->ID)
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
            $this->assignTermRelationships($originalId, $newId);
        }
        $this->assignProductToCategories($originalId, $newId);
        $this->assignTagsToProduct($originalId, $newId);
    }

    private function createPostMeta($originalId, $newId)
    {
        $postMetas = get_post_meta($originalId, '', true);
        $traduire_sans_migraine_inventory_linked_find = false;
        $currentPostMeta = get_post_meta($newId);
        foreach ($postMetas as $metaKey => $value) {
            if ($metaKey === "traduire_sans_migraine_inventory_linked") {
                $traduire_sans_migraine_inventory_linked_find = true;
            }
            if (isset($currentPostMeta[$metaKey])) {
                continue;
            }
            $metaValue = $value[0];
            if ($metaKey === "_sku" && !empty($metaValue)) {
                $metaValue .= "-" . $this->codeTo;
            } else if ($metaKey === "_default_attributes") {
                $metaValue = unserialize($metaValue);
                foreach ($metaValue as $taxonomy => $name) {
                    $term = get_term_by("slug", $name, $taxonomy);
                    if (empty($term) || is_wp_error($term)) {
                        continue;
                    }
                    $translations = TranslationTerms::findTranslationFor($term->term_id);
                    $termIdTranslated = $translations->getTranslation($this->codeTo);
                    if (empty($termIdTranslated)) {
                        continue;
                    }
                    $term = get_term_by("id", $termIdTranslated, $taxonomy);
                    if (empty($term)) {
                        continue;
                    }
                    $metaValue[$taxonomy] = $term->slug;
                }
            } else if (strstr($metaKey, "attribute_")) {
                echo "metaKey : " . $metaKey . "\n\n";
                $taxonomy = str_replace("attribute_", "", $metaKey);
                $term = get_term_by("slug", $metaValue, $taxonomy);
                if (empty($term) || is_wp_error($term)) {
                    echo "\t term not found\n\n";
                    continue;
                }
                $translations = TranslationTerms::findTranslationFor($term->term_id);
                $termIdTranslated = $translations->getTranslation($this->codeTo);
                if (empty($termIdTranslated)) {
                    echo "\t translation not found\n\n";
                    continue;
                }
                $term = get_term_by("id", $termIdTranslated, $taxonomy);
                if (empty($term)) {
                    continue;
                }
                $metaValue = $term->slug;
            } else {
                $unserialized = @unserialize($metaValue);
                if ($unserialized !== false) {
                    $metaValue = $unserialized;
                }
            }
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
        $sku = $productMetaLookup->sku;
        if (!empty($sku)) {
            $sku = get_post_field("post_name", $newId);
        }
        $wpdb->insert($wpdb->prefix . "wc_product_meta_lookup", [
            "product_id" => $newId,
            "sku" => $sku,
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
        $productAttributesLookup = $wpdb->get_results($wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d", $originalId));
        foreach ($productAttributesLookup as $productAttributeLookup) {
            if ($productAttributeLookup->product_or_parent_id === $this->originalObject->ID) {
                $productOrParentId = $this->translatedPostId;
            } else if ($productAttributeLookup->product_or_parent_id === $originalId) {
                $productOrParentId = $newId;
            } else {
                $translations = TranslationPost::findTranslationFor($productAttributeLookup->product_or_parent_id);
                $productOrParentId = $translations->getTranslation($this->codeTo);
            }
            if (empty($productOrParentId)) {
                continue;
            }
            $translations = TranslationTerms::findTranslationFor($productAttributeLookup->term_id);
            $termId = $translations->getTranslation($this->codeTo);
            if (empty($termId)) {
                continue;
            }

            $data = [
                "product_id" => $newId,
                "product_or_parent_id" => $productOrParentId,
                "taxonomy" => $productAttributeLookup->taxonomy,
                "term_id" => $termId,
                "is_variation_attribute" => $productAttributeLookup->is_variation_attribute,
                "in_stock" => $productAttributeLookup->in_stock,
            ];

            $wpdb->insert($wpdb->prefix . "wc_product_attributes_lookup", $data);
        }
    }

    private function assignTermRelationships($originalId, $newId)
    {
        global $wpdb;
        $termRelationships = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->term_relationships tr LEFT JOIN $wpdb->term_taxonomy tt ON tr.term_taxonomy_id=tt.term_taxonomy_id WHERE tr.object_id = %d", $originalId));
        $taxonomiesToCopy = ["product_type", "product_visibility", "product_shipping_class"];
        $attributes = [];
        foreach (wc_get_attribute_taxonomies() as $attribute) {
            $attributes[] = "pa_" . $attribute->attribute_name;
        }
        foreach ($termRelationships as $termRelationship) {
            if (in_array($termRelationship->taxonomy, $attributes)) {
                $translations = TranslationTerms::findTranslationFor($termRelationship->term_id);
                $translatedTerm = $translations->getTranslation($this->codeTo);
                if (!$translatedTerm) {
                    continue;
                }
                $translatedTerm = get_term_by("id", $translatedTerm, $termRelationship->taxonomy);
                $wpdb->insert($wpdb->term_relationships, [
                    "object_id" => $newId,
                    "term_taxonomy_id" => $translatedTerm->term_taxonomy_id,
                    "term_order" => $termRelationship->term_order
                ]);
                continue;
            }
            if (in_array($termRelationship->taxonomy, $taxonomiesToCopy)) {
                $wpdb->insert($wpdb->term_relationships, [
                    "object_id" => $newId,
                    "term_taxonomy_id" => $termRelationship->term_taxonomy_id,
                    "term_order" => $termRelationship->term_order
                ]);
            }
        }
    }

    private function assignProductToCategories($originalId, $newId)
    {
        global $wpdb;
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
            $translatedTerm = get_term_by("id", $translatedCategory, "product_cat");
            $wpdb->insert($wpdb->term_relationships, [
                "object_id" => $newId,
                "term_taxonomy_id" => $translatedTerm->term_taxonomy_id,
                "term_order" => 0
            ]);
        }
    }

    private function assignTagsToProduct($originalId, $newId)
    {
        global $wpdb;

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
            $translatedTag = get_term_by("id", $translatedTag, "product_tag");
            $wpdb->insert($wpdb->term_relationships, [
                "object_id" => $newId,
                "term_taxonomy_id" => $translatedTag->term_taxonomy_id,
                "term_order" => 0
            ]);
        }
    }

    protected function handleDefaultMetaPosts()
    {
    }
}
