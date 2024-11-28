<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Products;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends Translatable\Posts\PrepareTranslation
{
    public function prepareDataToTranslate()
    {
        parent::prepareDataToTranslate();
        $this->addCategories();
        $this->addTermsLookup();
        $this->addChildren();
        $this->addTags();
    }

    private function addCategories()
    {
        $categories = get_the_terms($this->object->ID, "product_cat");
        if (!is_array($categories)) {
            return;
        }
        $handleCategories = [];
        foreach ($categories as $category) {
            if (in_array($category->term_id, $handleCategories)) {
                continue;
            }
            $this->addCategory($category);
            $handleCategories[] = $category->term_id;
            $handleCategories = array_merge($handleCategories, get_ancestors($category->term_id, "product_cat"));
        }
    }

    private function addCategory($category)
    {
        $translations = TranslationTerms::findTranslationFor($category->term_id);
        if (!empty($translations->getTranslation($this->codeTo))) {
            return;
        }
        $this->action->addChild([
            "objectId" => $category->term_id,
            "actionType" => DAOActions::$ACTION_TYPE["TERMS"]
        ]);
    }

    private function addTermsLookup()
    {
        global $wpdb;

        $productAttributesLookup = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup 
                WHERE product_id = %d 
                   OR product_or_parent_id = %d",
                $this->object->ID, $this->object->ID)
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
            $this->action->addChild([
                "objectId" => $term->term_id,
                "actionType" => DAOActions::$ACTION_TYPE["TERMS"]
            ]);
        }

        foreach ($attributesName as $attributeName => $value) {
            $this->addAttribute($attributeName);
        }
    }

    private function addAttribute($attributeName)
    {
        global $wpdb;

        $attribute = $wpdb->get_row($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='%s'", $attributeName));
        if (!$attribute) {
            return;
        }
        $translations = TranslationAttribute::findTranslationFor($attribute->attribute_id);
        if (!empty($translations->getTranslation($this->codeTo))) {
            return;
        }
        $this->action->addChild([
            "objectId" => $attribute->attribute_id,
            "actionType" => DAOActions::$ACTION_TYPE["ATTRIBUTES"]
        ]);
    }

    private function addChildren()
    {
        global $wpdb;
        $postsChildren = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type NOT IN ('revision', 'attachment', 'auto-draft')", $this->object->ID));
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
        $tags = get_the_terms($this->object->ID, "product_tag");
        if (!is_array($tags)) {
            return;
        }
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            if (!empty($translations->getTranslation($this->codeTo))) {
                continue;
            }
            $this->action->addChild([
                "objectId" => $tag->term_id,
                "actionType" => DAOActions::$ACTION_TYPE["TERMS"]
            ]);
        }
    }
}