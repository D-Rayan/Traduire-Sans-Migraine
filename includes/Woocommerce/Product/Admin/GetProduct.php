<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class GetProduct
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('wp_ajax_traduire-sans-migraine_get_product', [$this, 'getProduct']);
    }

    public function getProduct()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }

        if (!isset($_GET["productId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }

        $productId = $_GET["productId"];
        $status = get_post_status($productId);
        if (!$status || $status == "trash" || $status == "auto-draft") {
            $translations = [];
            $languages = $tsm->getPolylangManager()->getLanguagesActives();
            $languageId = null;
            foreach ($languages as $slug => $language) {
                if ($language["default"]) {
                    $languageId = $language["id"];
                    break;
                }
            }
        } else {
            $translation = TranslationPost::findTranslationFor($productId);
            $translations = [];
            foreach ($translation->getTranslations() as $slug => $id) {
                if (empty($id)) {
                    $translations[$slug] = null;
                    continue;
                }
                $status = get_post_status($id);
                if (!$status || $status == "trash" || $status == "auto-draft") {
                    $translations[$slug] = null;
                    continue;
                }
                $translations[$slug] = [
                    "label" => get_post_field("post_name", $id),
                    "id" => $id
                ];
            }
            $language = LanguagePost::getLanguage($productId);
            $languageId = isset($language["id"]) ? $language["id"] : null;
        }

        wp_send_json_success([
            "translations" => $translations,
            "languageId" => $languageId,
            "product" => wc_get_product($productId)
        ]);
    }
}

$GetProduct = new GetProduct();
$GetProduct->init();