<?php

namespace TraduireSansMigraine\Woocommerce\Product;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class Query
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('pre_get_posts', [$this, 'addLanguageFilterQuery'], 100);
    }

    public function addLanguageFilterQuery($query)
    {
        global $tsm;
        if (!is_admin() && $query->get('post_type') === "product") {
            $current = $tsm->getPolylangManager()->getCurrentLanguageSlug();
            $actives = $tsm->getPolylangManager()->getLanguagesActives();
            if (empty($current) || !isset($actives[$current]["id"])) {
                return;
            }
            $term_taxonomy_id = LanguagePost::getTermTaxonomyId($actives[$current]["id"]);
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