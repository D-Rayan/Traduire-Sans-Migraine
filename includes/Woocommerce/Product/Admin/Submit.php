<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;


use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class Submit
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action("save_post_product", [$this, "saveProduct"], 10, 3);
        add_filter("woocommerce_rest_pre_insert_product_object", [$this, "saveProduct_Gutenberg"], 10, 3);
    }

    public function saveProduct($postId, $post, $update)
    {
        if (!isset($_POST["traduire_sans_migraine_translations"]) || !isset($_POST["traduire_sans_migraine_language"]) || !isset($_POST["traduire_sans_migraine_inventory_linked"])) {
            return;
        }
        update_post_meta($postId, "traduire_sans_migraine_inventory_linked", $_POST["traduire_sans_migraine_inventory_linked"] == "1" ? 1 : 0);
        do_action("tsm_save_entity", $postId, $_POST["traduire_sans_migraine_language"], $_POST["traduire_sans_migraine_translations"], true);
    }

    public function saveProduct_Gutenberg($product, $request, $creating)
    {
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $languagesById = [];
        foreach ($languages as $slug => $language) {
            $languagesById[$language["id"]] = $language;
        }
        $language = LanguagePost::getLanguage($product->get_id());
        if (isset($request["selectedLanguage"]) && isset($languagesById[$request["selectedLanguage"]])) {
            $language = $languagesById[$request["selectedLanguage"]];
            LanguagePost::setLanguage($product->get_id(), $language["code"]);
        }
        if (isset($request["translations"]) && is_array($request["translations"])) {
            $translations = [];
            foreach ($request["translations"] as $slug => $translation) {
                if (!isset($languages[$slug])) {
                    continue;
                }
                if ($slug === $language["code"]) {
                    $translations[$languages[$slug]["id"]] = $product->get_id();
                    continue;
                }
                if (empty($translation) || !isset($translation["id"])) {
                    continue;
                }
                $translations[$languages[$slug]["id"]] = (int)$translation["id"];
            }
            do_action("tsm_save_entity", $product->get_id(), $language["id"], $translations, true);
        }
        if (isset($request["traduire-sans-migraine-inventory-linked"])) {
            update_post_meta($product->get_id(), "traduire_sans_migraine_inventory_linked", $request["traduire-sans-migraine-inventory-linked"] == "true" ? 1 : 0);
        }
        return $product;
    }
}

$Submit = new Submit();
$Submit->init();
