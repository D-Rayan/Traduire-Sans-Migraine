<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;


class Submit
{
    public function __construct()
    {

    }

    public function init()
    {
        $taxonomies = apply_filters("tsm-wc-get-terms-allowed", []);
        foreach ($taxonomies as $taxonomy) {
            add_action("saved_" . $taxonomy, [$this, "saveCategory"], 10, 4);
        }

    }

    public function saveCategory($termId, $termTaxonomyId, $taxonomy, $term)
    {
        if (!isset($term["traduire_sans_migraine_translations"]) || !isset($term["traduire_sans_migraine_language"])) {
            return;
        }
        do_action("tsm_save_entity", $termId, $term["traduire_sans_migraine_language"], $term["traduire_sans_migraine_translations"], false);
    }
}

$Submit = new Submit();
$Submit->init();
