<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class Query
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('pre_get_posts', [$this, 'filterProducts'], 100);
    }

    public function filterProducts($query)
    {
        global $tsm;
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        if (is_admin() && $query->get('post_type') === "product" && $currentLanguage) {
            $actives = $tsm->getPolylangManager()->getLanguagesActives();
            if (empty($currentLanguage) || !isset($actives[$currentLanguage]["id"])) {
                return;
            }
            $term_taxonomy_id = LanguagePost::getTermTaxonomyId($actives[$currentLanguage]["id"]);
            if (!$term_taxonomy_id) {
                return;
            }
            $query->set('tax_query', [
                [
                    'taxonomy' => 'language',
                    'field' => 'term_taxonomy_id',
                    'terms' => $term_taxonomy_id,
                ]
            ]);
        }
    }
}

$Query = new Query();
$Query->init();