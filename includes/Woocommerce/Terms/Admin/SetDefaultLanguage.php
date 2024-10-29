<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;

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
        // @todo : optimize to get product WITHOUT language
        $terms = $this->getTerms($this->getTaxonomies());
        foreach ($terms as $term) {
            $termId = $term->term_id;
            if (!empty(LanguageTerm::getLanguage($termId))) {
                continue;
            }
            LanguageTerm::setLanguage($termId, $defaultCode);
        }
    }

    private function getTerms($taxonomies)
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->query(
                "SELECT t.term_id FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                WHERE tt.taxonomy IN ('" . implode("','", $taxonomies) . "')"
            )
        );
    }

    private function getTaxonomies()
    {
        $taxonomies = ["product_cat", "product_tag"];
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attribute) {
            $attributeName = "pa_" . str_replace("pa_", "", $attribute->attribute_name);
            $taxonomies[] = $attributeName;
        }
        return $taxonomies;
    }
}

$SetDefaultLanguage = new SetDefaultLanguage();
$SetDefaultLanguage->init();
