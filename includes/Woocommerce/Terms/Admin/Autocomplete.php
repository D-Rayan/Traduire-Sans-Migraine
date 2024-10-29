<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;


use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Autocomplete
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('wp_ajax_tsm_wc_get_categories_not_linked', [$this, 'getCategoriesNotLinked']);
    }

    public function getCategoriesNotLinked()
    {
        global $wpdb, $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }

        if (!isset($_GET["term"]) || !isset($_GET["languageId"]) || !isset($_GET["currentLanguageId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }


        $search = $_GET["term"];
        $languageId = $_GET["languageId"];
        $taxonomy = isset($_GET["taxonomy"]) ? $_GET["taxonomy"] : "product_cat";
        $currentTermId = isset($_GET["currentId"]) ? $_GET["currentId"] : 0;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $language = null;
        foreach ($languages as $slug => $lang) {
            if ($lang["id"] == $languageId) {
                $language = $lang;
                break;
            }
        }
        if (!$language) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $currentLanguageId = $_GET["currentLanguageId"];
        $currentLanguage = null;
        foreach ($languages as $slug => $lang) {
            if ($lang["id"] == $currentLanguageId) {
                $currentLanguage = $lang;
                break;
            }
        }
        if (!$currentLanguage) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        if ($currentTermId) {
            $translations = TranslationTerms::findTranslationFor($currentTermId);
            $term = get_term($currentTermId);
            $taxonomy = isset($term) ? $term->taxonomy : $taxonomy;
        } else {
            $translations = new TranslationTerms();
        }
        $allowedTermId = $translations->getTranslation($language["code"]);
        $query = $wpdb->prepare("SELECT t.name AS label, t.term_id AS id FROM $wpdb->terms t 
            LEFT JOIN $wpdb->term_relationships tr ON tr.object_id=t.term_id 
            LEFT JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
            LEFT JOIN $wpdb->term_taxonomy tt2 ON tt2.term_id=t.term_id
           WHERE t.name LIKE '%s'
             AND ((t.term_id=%d) OR (tt.term_id=%d 
             AND tt2.taxonomy='%s' 
             AND t.term_id!=%d 
             AND (SELECT COUNT(*) FROM $wpdb->term_taxonomy tt3 WHERE tt3.taxonomy='term_translations' AND (tt3.description LIKE CONCAT('%%:', t.term_id, ';%%') OR tt3.description LIKE CONCAT('%%:\"', t.term_id, '\";%%')) AND tt3.description LIKE '%s')=0))
           GROUP BY t.term_id
           LIMIT 10", "%" . $search . "%", $allowedTermId, LanguageTerm::getTermIdByLanguage($languageId), $taxonomy, $currentTermId, '%s:' . strlen($currentLanguage["code"]) . ':"' . $currentLanguage["code"] . '";%');
        $categories = $wpdb->get_results($query);
        foreach ($categories as $key => $category) {
            $translations = TranslationTerms::findTranslationFor($category->id);
            $categories[$key]->translations = $translations->getTranslations();
            foreach ($categories[$key]->translations as $lang => $termId) {
                if ($termId == $category->id) {
                    unset($categories[$key]->translations[$lang]);
                    continue;
                }
                $categories[$key]->translations[$lang] = [
                    "label" => get_term_field("name", $termId),
                    "id" => $termId
                ];
            }
        }
        wp_send_json_success($categories);
    }
}

$Autocomplete = new Autocomplete();
$Autocomplete->init();
