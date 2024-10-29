<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;


use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class Autocomplete
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('wp_ajax_tsm_wc_get_products_not_linked', [$this, 'getProductsNotLinked']);
    }

    public function getProductsNotLinked()
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
        $currentId = isset($_GET["currentId"]) ? $_GET["currentId"] : 0;
        $translations = TranslationPost::findTranslationFor($currentId);
        $allowedProductId = $translations->getTranslation($language["code"]);

        $query = $wpdb->prepare("SELECT p.post_name AS label, p.ID AS id FROM $wpdb->posts p 
            INNER JOIN $wpdb->term_relationships tr ON tr.object_id=p.ID 
            INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
           WHERE p.post_name LIKE '%s' AND ((
               p.ID=%d
           ) OR (
                     tt.term_id=%d 
                     AND p.post_type='product' 
                     AND p.ID!=%d 
                     AND p.post_status NOT IN ('trash', 'auto-draft')
                     AND (SELECT COUNT(*) FROM $wpdb->term_taxonomy tt3 WHERE tt3.taxonomy='post_translations' AND tt3.description LIKE CONCAT('%%i:', p.ID, ';%%') AND tt3.description LIKE '%s')=0
               )) GROUP BY p.ID
            LIMIT 10
         ", "%" . $search . "%", $allowedProductId, $languageId, $currentId, '%s:' . strlen($currentLanguage["code"]) . ':"' . $currentLanguage["code"] . '";%');

        $products = $wpdb->get_results($query);
        foreach ($products as $key => $product) {
            $translations = TranslationPost::findTranslationFor($product->id);
            $products[$key]->translations = $translations->getTranslations();
            foreach ($products[$key]->translations as $lang => $productId) {
                if ($productId == $product->id) {
                    unset($products[$key]->translations[$lang]);
                    continue;
                }
                $products[$key]->translations[$lang] = [
                    "label" => get_post_field("post_name", $productId),
                    "id" => $productId
                ];
            }
        }
        wp_send_json_success($products);
    }
}

$Autocomplete = new Autocomplete();
$Autocomplete->init();

