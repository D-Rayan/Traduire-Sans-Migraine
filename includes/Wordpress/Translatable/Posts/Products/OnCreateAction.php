<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts\Products;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class OnCreateAction extends Translatable\Posts\OnCreateAction
{
    protected function prepareChildren()
    {
        parent::prepareChildren();
        $this->addCategories();
        $this->addTermsLookup();
        $this->addTags();
    }

    private function addCategories()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["translateCategories"])) {
            return;
        }
        $categories = get_the_terms($this->action->getObjectId(), "product_cat");
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
        if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
            return;
        }
        $this->addChildren($category->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
    }

    private function addTermsLookup()
    {
        global $wpdb;

        $productAttributesLookup = $wpdb->get_results(
            $wpdb->prepare("SELECT * FROM {$wpdb->prefix}wc_product_attributes_lookup 
                WHERE product_id = %d 
                   OR product_or_parent_id = %d",
                $this->action->getObjectId(), $this->action->getObjectId())
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
            if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
                continue;
            }
            $this->addChildren($term->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
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
        if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
            return;
        }
        $this->addChildren($attribute->attribute_id, DAOActions::$ACTION_TYPE["ATTRIBUTES"]);
    }

    private function addTags()
    {
        $tags = get_the_terms($this->action->getObjectId(), "product_tag");
        if (!is_array($tags)) {
            return;
        }
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
                continue;
            }
            $this->addChildren($tag->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
        }
    }
}