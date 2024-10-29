<?php

namespace TraduireSansMigraine\Woocommerce\Attributes;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Display
{
    public function __construct()
    {

    }

    public function filterProductAttributes($termName, $term, $attribute, $product)
    {
        // echo "termname : " . $termName . "<br>";
        // echo "attribute : " . $attribute . "<br>";
        $translations = TranslationTerms::findTranslationFor($term->term_id);


        return $termName;
    }

    public function filterProductAttributesLabel($label, $name, $product)
    {
        global $tsm, $wpdb;
        if (is_admin()) {
            return $label;
        }
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $slug = str_replace("pa_", "", $name);
        $id = $wpdb->get_var($wpdb->prepare("SELECT attribute_id FROM {$wpdb->prefix}woocommerce_attribute_taxonomies WHERE attribute_name='%s'", $slug));
        if (!$id) {
            return $label;
        }
        $translations = TranslationAttribute::findTranslationFor($id);
        $translation = $translations->getTranslation($currentLanguage);
        if (empty($translation)) {
            return $label;
        }

        return $translation;
    }

    public function init()
    {
        $this->registerHooks();
    }

    private function registerHooks()
    {
        add_filter('woocommerce_variation_option_name', [$this, "filterProductAttributes"], 10, 4);
        add_filter('woocommerce_attribute_label', [$this, "filterProductAttributesLabel"], 10, 3);
    }
}

$Display = new Display();
$Display->init();
