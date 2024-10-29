<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Woocommerce;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class TranslateBulk
{

    private $language;
    private $translating = 0;

    public function __construct()
    {
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function loadHooks()
    {
        add_action("wp_ajax_traduire-sans-migraine_woocommerce_translate_bulk", [$this, "translateBulk"]);
    }

    public function translateBulk()
    {
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["languageSlug"]) || !isset($_POST["objectType"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$_POST["languageSlug"]])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        $this->language = $languages[$_POST["languageSlug"]];
        $objectType = $_POST["objectType"];
        switch ($objectType) {
            case "pages":
                $this->createPagesActions();
                break;
            case "categories":
                $this->createCategoriesActions();
                break;
            case "attributes":
                $this->createAttributesActions();
                break;
            case "emails":
                $this->createEmailsActions();
                break;
            case "tags":
                $this->createTagsActions();
                break;
            case "products":
                $this->createProductsActions();
                break;
        }
        wp_send_json_success([
            "translating" => $this->translating,
        ]);
        wp_die();
    }

    private function createPagesActions()
    {
        array_map([$this, "updatePageState"], [
            "woocommerce_shop_page_id",
            "woocommerce_cart_page_id",
            "woocommerce_checkout_page_id",
            "woocommerce_myaccount_page_id",
            "woocommerce_terms_page_id",
        ]);
    }

    private function createCategoriesActions()
    {
        $categories = get_terms([
            "taxonomy" => "product_cat",
            "hide_empty" => false,
        ]);
        foreach ($categories as $category) {
            $translations = TranslationTerms::findTranslationFor($category->term_id);
            if (!empty($translations->getTranslation($this->language["code"]))) {
                continue;
            }
            $this->addAction($category->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
        }
    }

    private function addAction($id, $type)
    {
        $existingAction = AbstractAction::loadByObjectId($id, $this->language["code"], $type);
        if ($existingAction && $existingAction->willBeProcessing()) {
            return;
        }
        $action = AbstractAction::getInstance([
            "objectId" => $id,
            "slugTo" => $this->language["code"],
            "origin" => DAOActions::$ORIGINS["QUEUE"],
            "actionType" => $type
        ]);
        $action->save();
        $this->translating++;
    }

    private function createAttributesActions()
    {
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attribute) {
            $translations = TranslationAttribute::findTranslationFor($attribute->attribute_id);
            if (empty($translations->getTranslation($this->language["code"]))) {
                $action = new Translatable\Attributes\Action([
                    "objectId" => $attribute->attribute_id,
                    "slugTo" => $this->language["code"],
                    "origin" => DAOActions::$ORIGINS["QUEUE"]
                ]);
                $action->save();
            }
            $attributeName = "pa_" . str_replace("pa_", "", $attribute->attribute_name);
            $terms = get_terms([
                "taxonomy" => $attributeName,
                "hide_empty" => false,
            ]);
            foreach ($terms as $term) {
                $translations = TranslationTerms::findTranslationFor($term->term_id);
                if ($translations->getTranslation($this->language["code"])) {
                    continue;
                }
                $this->addAction($term->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
            }
        }
    }

    private function createEmailsActions()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            $hadToTranslateIt = $this->updateEmailState($email, "additional_content", "tsm-language-" . $this->language["code"] . "-content") ||
                $this->updateEmailState($email, "subject", "tsm-language-" . $this->language["code"] . "-subject") ||
                $this->updateEmailState($email, "header", "tsm-language-" . $this->language["code"] . "-header");
            if (!$hadToTranslateIt) {
                continue;
            }
            $this->addAction($email->id, DAOActions::$ACTION_TYPE["EMAIL"]);
        }
    }

    private function updateEmailState($email, $originalField, $field)
    {
        if (empty($email->settings[$originalField]) || !empty($email->settings[$field])) {
            return false;
        }
        return true;
    }

    private function createTagsActions()
    {
        $tags = get_terms([
            "taxonomy" => "product_tag",
            "hide_empty" => false,
        ]);
        foreach ($tags as $tag) {
            $translations = TranslationTerms::findTranslationFor($tag->term_id);
            if (!empty($translations->getTranslation($this->language["code"]))) {
                continue;
            }
            $this->addAction($tag->term_id, DAOActions::$ACTION_TYPE["TERMS"]);
        }
    }

    private function createProductsActions()
    {
        global $wpdb;
        $defaultLanguage = Language::getDefaultLanguage();
        if (empty($defaultLanguage["code"])) {
            return;
        }

        $query = $wpdb->prepare("SELECT p.ID AS id FROM $wpdb->posts p 
            INNER JOIN $wpdb->term_relationships tr ON tr.object_id=p.ID 
            INNER JOIN $wpdb->term_taxonomy tt ON tt.term_taxonomy_id=tr.term_taxonomy_id
           WHERE tt.term_id=%d 
                     AND p.post_type='product' 
                     AND p.post_status NOT IN ('trash', 'auto-draft')
                     AND (SELECT COUNT(*) FROM $wpdb->term_taxonomy tt3 WHERE tt3.taxonomy='post_translations' AND tt3.description LIKE CONCAT('%%i:', p.ID, ';%%') AND tt3.description LIKE %s)=0
                GROUP BY p.ID
         ", $defaultLanguage["id"], '%s:' . strlen($this->language["code"]) . ':"' . $this->language["code"] . '";%');
        $products = $wpdb->get_results($query);
        foreach ($products as $product) {
            $this->addAction($product->id, DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"]);
        }
    }

    public function updatePageState($optionName)
    {
        global $tsm;
        $pageId = get_option($optionName);
        if (empty($pageId)) {
            return;
        }
        $translations = TranslationPost::findTranslationFor($pageId);
        $pageIdTranslated = $translations->getTranslation($this->language["code"]);
        if (!empty($pageIdTranslated)) {
            return;
        }
        $this->addAction($pageId, DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"]);
    }

}

$TranslateBulk = new TranslateBulk();
$TranslateBulk->init();