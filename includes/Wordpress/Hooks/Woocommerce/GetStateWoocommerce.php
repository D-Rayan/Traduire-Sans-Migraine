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

class GetStateWoocommerce
{
    private $state = [
        "pages" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
            "draft" => 0,
        ],
        "categories" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
        ],
        "attributes" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
        ],
        "emails" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
        ],
        "tags" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
        ],
        "products" => [
            "missing" => 0,
            "estimatedQuota" => 0,
            "translating" => 0,
        ]
    ];
    private $language;

    public function __construct()
    {
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function loadHooks()
    {
        add_action("wp_ajax_traduire-sans-migraine_woocommerce_get_state", [$this, "getState"]);
    }

    public function getState()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["languageSlug"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$_GET["languageSlug"]])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        $this->language = $languages[$_GET["languageSlug"]];
        $this->setPagesStates();
        $this->setCategoriesStates();
        $this->setAttributesStates();
        $this->setEmailsStates();
        $this->setTagsStates();
        $this->setProductsStates();

        wp_send_json_success($this->state);
        wp_die();
    }

    private function setPagesStates()
    {
        array_map([$this, "updatePageState"], [
            "woocommerce_shop_page_id",
            "woocommerce_cart_page_id",
            "woocommerce_checkout_page_id",
            "woocommerce_myaccount_page_id",
            "woocommerce_terms_page_id",
        ]);
    }

    private function setCategoriesStates()
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
            if ($this->actionIsInQueue($category->term_id, DAOActions::$ACTION_TYPE["TERMS"])) {
                $this->state["categories"]["translating"]++;
                continue;
            }
            $this->state["categories"]["missing"]++;
            $this->state["categories"]["estimatedQuota"] += strlen($category->name) + strlen($category->description);
        }
    }

    private function actionIsInQueue($id, $type)
    {
        $action = AbstractAction::loadByObjectId($id, $this->language["code"], $type);
        return !empty($action);
    }

    private function setAttributesStates()
    {
        $attributes = wc_get_attribute_taxonomies();
        foreach ($attributes as $attribute) {
            $estimatedQuota = 0;
            $isMissing = false;
            $translations = TranslationAttribute::findTranslationFor($attribute->attribute_id);
            if (empty($translations->getTranslation($this->language["code"]))) {
                if (!$this->actionIsInQueue($attribute->attribute_id, DAOActions::$ACTION_TYPE["ATTRIBUTES"])) {
                    $isMissing = true;
                    $estimatedQuota += strlen($attribute->attribute_label);
                } else {
                    $this->state["attributes"]["translating"]++;
                }
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
                if ($this->actionIsInQueue($term->term_id, DAOActions::$ACTION_TYPE["TERMS"])) {
                    $this->state["attributes"]["translating"]++;
                    continue;
                }
                $isMissing = true;
                $estimatedQuota += strlen($term->name) + strlen($term->description);
            }
            if ($isMissing) {
                $this->state["attributes"]["missing"]++;
                $this->state["attributes"]["estimatedQuota"] += $estimatedQuota;
            }
        }
    }

    private function setEmailsStates()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            $estimatedQuota = $this->updateEmailState($email, "additional_content", "tsm-language-" . $this->language["code"] . "-content");
            $estimatedQuota += $this->updateEmailState($email, "subject", "tsm-language-" . $this->language["code"] . "-subject");
            $estimatedQuota += $this->updateEmailState($email, "header", "tsm-language-" . $this->language["code"] . "-header");
            if ($estimatedQuota > 0) {
                if ($this->actionIsInQueue($email->id, DAOActions::$ACTION_TYPE["EMAIL"])) {
                    $this->state["emails"]["translating"]++;
                    continue;
                }
                $this->state["emails"]["missing"]++;
                $this->state["emails"]["estimatedQuota"] += $estimatedQuota;
            }
        }
    }

    private function updateEmailState($email, $originalField, $field)
    {
        if (empty($email->settings[$originalField]) || !empty($email->settings[$field])) {
            return 0;
        }
        return strlen($email->settings[$originalField]);
    }

    private function setTagsStates()
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
            if ($this->actionIsInQueue($tag->term_id, DAOActions::$ACTION_TYPE["TERMS"])) {
                $this->state["tags"]["translating"]++;
                continue;
            }
            $this->state["tags"]["missing"]++;
            $this->state["tags"]["estimatedQuota"] += strlen($tag->name) + strlen($tag->description);
        }
    }

    private function setProductsStates()
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
        $this->state["products"]["query"] = $query;
        $products = $wpdb->get_results($query);
        foreach ($products as $product) {
            if ($this->actionIsInQueue($product->id, DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"])) {
                $this->state["products"]["translating"]++;
                continue;
            }
            $this->state["products"]["missing"]++;
            $temporaryAction = new Translatable\Posts\Action([
                "objectId" => $product->id,
                "slugTo" => $this->language["code"],
                "origin" => "HOOK"
            ]);
            $this->state["products"]["estimatedQuota"] += $temporaryAction->getEstimatedQuota();
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
            if (get_post_status($pageIdTranslated) === "draft") {
                $this->state["pages"]["draft"]++;
            }
            return;
        }
        if ($this->actionIsInQueue($pageId, DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"])) {
            $this->state["pages"]["translating"]++;
            return;
        }
        $this->state["pages"]["missing"]++;
        $temporaryAction = new Translatable\Posts\Action([
            "objectId" => $pageId,
            "slugTo" => $this->language["code"],
            "origin" => "HOOK"
        ]);
        $this->state["pages"]["estimatedQuota"] += $temporaryAction->getEstimatedQuota();
    }

}

$GetStateWoocommerce = new GetStateWoocommerce();
$GetStateWoocommerce->init();