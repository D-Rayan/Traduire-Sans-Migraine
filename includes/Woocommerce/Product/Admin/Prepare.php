<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Prepare
{
    private $dataToTranslate;
    private $postId;
    private $codeTo;

    public function __construct()
    {

    }

    public function init()
    {
        add_filter("tsm-prepare-data-to-translate", [$this, "prepareDataToTranslate"], 10, 3);
    }

    public function prepareDataToTranslate($dataToTranslate, $postId, $codeTo)
    {
        if (get_post_type($postId) !== "product") {
            return $dataToTranslate;
        }
        $this->dataToTranslate = $dataToTranslate;
        $this->postId = $postId;
        $this->codeTo = $codeTo;

        $this->addCategories();
        $this->addTermsLookup();
        $this->addChildren();
        $this->addTags();

        return $this->dataToTranslate;
    }

    private function addCategories()
    {
        $categories = get_the_terms($this->postId, "product_cat");
        if (!is_array($categories)) {
            return;
        }
        foreach ($categories as $category) {
            $this->addCategory($category);
        }
    }

    private function addCategory($category)
    {
        $translations = TranslationTerms::findTranslationFor($category->term_id);
        if (empty($translations->getTranslation($this->codeTo))) {
            $this->dataToTranslate["categories_" . $category->term_id . "_name"] = $category->name;
            $this->dataToTranslate["categories_" . $category->term_id . "_description"] = $category->description;
        }
        if (is_numeric($category->parent) && $category->parent > 0) {
            if (isset($this->dataToTranslate["categories_" . $category->parent . "_name"])) {
                return;
            }
            $term = get_term($category->parent, "product_cat");
            $this->addCategory($term);
        }
    }

    private function addTermsLookup()
    {
        global $wpdb;

        $productAttributesLookup = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup WHERE product_id = %d OR product_or_parent_id = %d", $this->postId, $this->postId)
        );

        $attributesName = [];
        foreach ($productAttributesLookup as $productAttributeLookup) {
            $term = get_term($productAttributeLookup->term_id);
            if (!$term) {
                continue;
            }
            $attributeName = str_replace("pa_", "", $productAttributeLookup->taxonomy);
            $attributesName[$attributeName] = true;
            $translations = TranslationTerms::findTranslationFor($term->term_id);
            if (!empty($translations->getTranslation($this->codeTo))) {
                continue;
            }
            $this->dataToTranslate["term_" . $productAttributeLookup->term_id] = $term->name;
        }

        foreach ($attributesName as $attributeName => $value) {
            $this->addAttribute($attributeName);
        }
    }

    private function addAttribute($attributeName)
    {
        global $wpdb;

        $attribute = $wpdb->get_row($wpdb->prepare("SELECT attribute_id, attribute_label FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='%s'", $attributeName));
        if (!$attribute) {
            return;
        }
        $translations = TranslationAttribute::findTranslationFor($attribute->attribute_id);
        if (!empty($translations->getTranslation($this->codeTo))) {
            return;
        }
        $this->dataToTranslate["attribute_" . $attribute->attribute_id] = $attribute->attribute_label;
    }

    private function addChildren()
    {
        global $wpdb;
        $postsChildren = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type NOT IN ('revision', 'attachment', 'auto-draft')", $this->postId));
        foreach ($postsChildren as $postChild) {
            if (!empty($postChild->post_title)) {
                $this->dataToTranslate["child_post_title_" . $postChild->ID] = $postChild->post_title;
            }
            if (!empty($postChild->post_content)) {
                $this->dataToTranslate["child_post_content_" . $postChild->ID] = $postChild->post_content;
            }
        }
    }

    private function addTags()
    {
        $tags = get_the_terms($this->postId, "product_tag");
        if (!is_array($tags)) {
            return;
        }
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            if (empty($translations->getTranslation($this->codeTo))) {
                $this->dataToTranslate["tags_" . $tag->term_id . "_name"] = $tag->name;
                $this->dataToTranslate["tags_" . $tag->term_id . "_description"] = $tag->description;
            }
        }
    }
}

$Prepare = new Prepare();
$Prepare->init();