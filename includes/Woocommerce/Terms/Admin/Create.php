<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Create
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action("tsm-woocommerce-category-translate", [$this, "translateCategory"], 10, 3);
    }

    public function translateCategory($categoryId, $dataTranslated, $intoLanguageCode)
    {
        global $wpdb;
        $translations = $this->loadTranslations($categoryId);
        if (!$translations) {
            return;
        }
        $taxonomyOriginal = $wpdb->get_row("SELECT * FROM $wpdb->term_taxonomy WHERE term_id = $categoryId");
        $parent = $taxonomyOriginal->parent;
        $prefix = "categories_";
        if ($taxonomyOriginal->taxonomy === "product_tag") {
            $prefix = "tags_";
        }
        if ($parent != 0) {
            $translationsParent = TranslationTerms::findTranslationFor($parent);
            if (empty($translationsParent->getTranslation($intoLanguageCode))) {
                do_action("tsm-woocommerce-category-translate", $parent, $dataTranslated, $intoLanguageCode);
                $translationsParent = TranslationTerms::findTranslationFor($parent);
            }
            $parent = $translationsParent->getTranslation($intoLanguageCode);
        }
        $newCategoryId = $this->createTerm($dataTranslated[$prefix . $categoryId . "_name"], sanitize_title($dataTranslated[$prefix . $categoryId . "_description"]));
        $wpdb->insert($wpdb->term_taxonomy, ["term_id" => $newCategoryId, "taxonomy" => $taxonomyOriginal->taxonomy, "description" => $dataTranslated["description"], "parent" => $parent, "count" => 0]);
        $this->copyCategoryMeta($categoryId, $newCategoryId);
        LanguageTerm::setLanguage($newCategoryId, $intoLanguageCode);
        $translations->addTranslation($intoLanguageCode, $newCategoryId);
    }

    private function loadTranslations($categoryId)
    {
        $translations = TranslationTerms::findTranslationFor($categoryId);
        if (!isset($translations)) {
            $translations = new TranslationTerms();
            $originalLanguage = LanguageTerm::getLanguage($categoryId);
            if (empty($originalLanguage)) {
                return null;
            }
            $translations->addTranslation($originalLanguage["code"], $categoryId);
        }
        return $translations;
    }

    private function createTerm($name, $slug)
    {
        global $wpdb;
        $wpdb->insert($wpdb->terms, ["name" => $name, "slug" => $slug, "term_group" => 0]);
        $termId = $wpdb->insert_id;
        return $termId;
    }

    private function copyCategoryMeta($categoryId, $newCategoryId)
    {
        global $wpdb;
        $categoryMeta = $wpdb->get_results("SELECT * FROM $wpdb->termmeta WHERE term_id = $categoryId");
        foreach ($categoryMeta as $meta) {
            if (strstr($meta->meta_key, "count")) {
                $meta->meta_value = 0;
            }
            $wpdb->insert($wpdb->termmeta, ["term_id" => $newCategoryId, "meta_key" => $meta->meta_key, "meta_value" => $meta->meta_value]);
        }
    }
}

$Create = new Create();
$Create->init();