<?php

namespace TraduireSansMigraine\Woocommerce\Terms;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Get
{
    public function __construct()
    {

    }

    public function init()
    {
        add_filter("pll_translation_url", [$this, "injectIntoPllUrlTranslated"], 10, 2);
        add_filter("tsm-wc-get-terms-allowed", [$this, "injectIntoWcTermsToHandle"]);
    }

    public function injectIntoWcTermsToHandle($terms)
    {
        global $wpdb;
        $allowedTerms = array_merge($terms, ["product_cat", "product_tag"]);
        $taxonomies = $wpdb->get_results("SELECT attribute_name FROM {$wpdb->prefix}woocommerce_attribute_taxonomies");
        if (is_array($taxonomies)) {
            foreach ($taxonomies as $taxonomy) {
                $allowedTerms[] = "pa_" . $taxonomy->attribute_name;
            }
        }

        return $allowedTerms;
    }

    public function injectIntoPllUrlTranslated($url, $slug)
    {
        global $tsm;
        if (!is_product_category()) {
            return $url;
        }
        if ($tsm->getPolylangManager()->getCurrentLanguageSlug() === $slug) {
            return $url;
        }
        $term = get_queried_object();
        $termId = $term->term_id;
        $translations = TranslationTerms::findTranslationFor($termId);
        if (empty($translations->getTranslation($slug))) {
            return $url;
        }
        $translatedTermId = $translations->getTranslation($slug);
        $translatedTerm = get_term($translatedTermId);
        if (!$translatedTerm) {
            return $url;
        }

        $urlSlug = $tsm->getPolylangManager()->getHomeUrl($slug);
        $urlOriginalSlug = $tsm->getPolylangManager()->getHomeUrl($tsm->getPolylangManager()->getCurrentLanguageSlug());
        return str_replace($urlOriginalSlug, $urlSlug, get_term_link($translatedTerm));
    }
}

$Get = new Get();
$Get->init();