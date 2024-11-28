<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class SetDefaultLanguage
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('tsm-set-default-language', [$this, 'setDefaultLanguage']);
        add_action('traduire-sans-migraine_enable_woocommerce', [$this, 'activationWoocommerce']);
    }

    public function activationWoocommerce()
    {
        $code = Language::getDefaultLanguage()["code"];
        if (empty($code)) {
            return;
        }
        $this->setDefaultLanguage($code);
    }

    public function setDefaultLanguage($defaultCode)
    {
        $products = $this->getProductsWithoutLanguage();
        foreach ($products as $product) {
            $product_id = $product->ID;
            if (!empty(LanguagePost::getLanguage($product_id))) {
                continue;
            }
            LanguagePost::setLanguage($product_id, $defaultCode);
        }
    }

    private function getProductsWithoutLanguage()
    {
        global $wpdb;

        $query = "SELECT p.ID FROM $wpdb->posts p
            WHERE p.post_type IN ('product', 'product_variation') AND 
                  p.ID NOT IN (SELECT tr.object_id FROM {$wpdb->term_relationships} tr 
                                INNER JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id 
                                WHERE tt.taxonomy = %s)";

        return $wpdb->get_results($wpdb->prepare($query, LanguagePost::getTaxonomy()));
    }
}

$SetDefaultLanguage = new SetDefaultLanguage();
$SetDefaultLanguage->init();
