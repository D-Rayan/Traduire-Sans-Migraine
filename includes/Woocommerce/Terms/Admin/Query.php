<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;

class Query
{
    public function __construct()
    {

    }

    public function init()
    {
        add_filter("terms_clauses", [$this, "filterTerms"], 10, 3);
    }

    public function filterTerms($terms, $taxonomies, $query)
    {
        global $tsm, $wpdb;
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        if (!$currentLanguage && isset($_GET["pll_post_id"])) {
            $language = LanguagePost::getLanguage($_GET["pll_post_id"]);
            $currentLanguage = $language ? $language["code"] : null;
        }
        if (!$currentLanguage) {
            return $terms;
        }
        $allowedTaxonomies = apply_filters("tsm-wc-get-terms-allowed", []);
        $shouldStop = true;
        foreach ($allowedTaxonomies as $allowedTaxonomy) {
            if (in_array($allowedTaxonomy, $taxonomies)) {
                $shouldStop = false;
                break;
            }
        }
        if ($shouldStop) {
            return $terms;
        }
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (isset($languages[$currentLanguage])) {
            $termId = LanguageTerm::getTermIdByLanguage($languages[$currentLanguage]["id"]);
            $terms["join"] .= " LEFT JOIN $wpdb->term_relationships pll_tr ON pll_tr.object_id = t.term_id";
            $terms["join"] .= " LEFT JOIN $wpdb->term_taxonomy pll_tt ON pll_tr.term_taxonomy_id = pll_tt.term_taxonomy_id";
            $terms["where"] .= " AND pll_tt.term_id = $termId";
        }

        return $terms;
    }
}

$Query = new Query();
$Query->init();