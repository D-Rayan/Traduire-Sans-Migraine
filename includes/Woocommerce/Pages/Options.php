<?php

namespace TraduireSansMigraine\Woocommerce\Pages;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class Options
{
    private $currentSlug;
    private $originalsIds = [];
    private $originalPermalinks = null;
    private $translatedIds = [];
    private $rules = null;

    public function __construct()
    {

    }

    public function init()
    {
        $this->prepareVariables();
        add_action('template_redirect', [$this, 'redirectToRightUrl']);
        add_filter("pll_translation_url", [$this, "injectIntoPllUrlTranslated"], 10, 2);
        add_filter('option_woocommerce_shop_page_id', [$this, 'getPageIdTranslated']);
        add_filter('option_woocommerce_cart_page_id', [$this, 'getPageIdTranslated']);
        add_filter('option_woocommerce_checkout_page_id', [$this, 'getPageIdTranslated']);
        add_filter('option_woocommerce_myaccount_page_id', [$this, 'getPageIdTranslated']);
        add_filter('option_woocommerce_terms_page_id', [$this, 'getPageIdTranslated']);
        add_filter('option_woocommerce_permalinks', [$this, 'getPermalinks']);
        add_filter('rewrite_rules_array', [$this, 'addRulesPolylangWooCommerce'], 10000);
        add_filter('get_pages_query_args', [$this, 'getPagesQueryArgs'], 10, 2);
        add_filter('woocommerce_json_search_found_pages', [$this, 'filterFoundPages']);
    }

    public function prepareVariables()
    {
        $this->loadOriginalsOptions();
    }

    private function loadOriginalsOptions()
    {
        $shopPageId = get_option('woocommerce_shop_page_id');
        $cartPageId = get_option('woocommerce_cart_page_id');
        $checkoutPageId = get_option('woocommerce_checkout_page_id');
        $accountPageId = get_option('woocommerce_myaccount_page_id');
        $termsPageId = get_option('woocommerce_terms_page_id');
        $this->originalPermalinks = get_option("woocommerce_permalinks");
        $this->originalsIds = [
            "shop_page" => $shopPageId,
            "cart_page" => $cartPageId,
            "checkout_page" => $checkoutPageId,
            "myaccount_page" => $accountPageId,
            "terms_page" => $termsPageId,
        ];
    }

    public function getPermalinks($permalinks)
    {
        global $tsm;
        if (is_admin()) {
            return $permalinks;
        }
        $this->currentSlug = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $isDefault = Language::getDefaultLanguage()["code"] === $this->currentSlug;
        if ($isDefault) {
            return $permalinks;
        }
        foreach ($this->originalPermalinks as $key => $permalink) {
            $permalinks[$key] = $this->currentSlug . "/" . $permalink;
        }
        return $permalinks;
    }

    public function addRulesPolylangWooCommerce($rules)
    {
        global $tsm;
        if (!empty($this->rules)) {
            return $this->rules;
        }

        $activesLanguages = $tsm->getPolylangManager()->getLanguagesActives();
        $page_rewrite_rules = [];
        foreach ($this->originalsIds as $originalId) {
            if (empty($originalId)) {
                continue;
            }
            $originalUri = get_page_uri($originalId);
            foreach ($rules as $condition => $rule) {
                if (!strstr($condition, $originalUri)) {
                    continue;
                }
                foreach ($activesLanguages as $slug => $language) {
                    if ($language["default"]) {
                        continue;
                    }
                    $translations = TranslationPost::findTranslationFor($originalId);
                    $translatedId = $translations->getTranslation($slug);
                    if (!$translatedId) {
                        continue;
                    }
                    $uri = get_page_uri($this->getPageIdTranslated($translatedId));
                    $newCondition = $slug . '/' . str_replace($originalUri, $uri, $condition);
                    if (strstr($rule, "?")) {
                        $newRule = $rule . '&lang=' . $slug;
                    } else {
                        $newRule = $rule . '?lang=' . $slug;
                    }
                    if (isset($rules[$newCondition]) || isset($page_rewrite_rules[$newCondition])) {
                        continue;
                    }
                    $page_rewrite_rules[$newCondition] = $newRule;
                }
            }
        }
        foreach ($this->originalPermalinks as $name => $completeUri) {
            if (empty($completeUri)) {
                continue;
            }
            foreach ($rules as $condition => $rule) {
                if (!strstr($condition, $completeUri)) {
                    continue;
                }
                foreach ($activesLanguages as $slug => $language) {
                    if ($language["default"]) {
                        continue;
                    }
                    $startUriLang = ltrim(wp_make_link_relative($tsm->getPolylangManager()->getHomeUrl($slug)), '/');
                    $newCondition = $startUriLang . $condition;
                    if (strstr($rule, "?")) {
                        $newRule = $rule . '&lang=' . $slug;
                    } else {
                        $newRule = $rule . '?lang=' . $slug;
                    }
                    if (isset($rules[$newCondition]) || isset($page_rewrite_rules[$newCondition])) {
                        continue;
                    }
                    $page_rewrite_rules[$newCondition] = $newRule;
                }
            }
        }
        $this->rules = array_merge($page_rewrite_rules, $rules);

        return $this->rules;
    }

    public function getPageIdTranslated($originalId)
    {
        if (is_admin()) {
            return $originalId;
        }
        $this->loadTranslatedIds($originalId);
        return $this->translatedIds[$originalId]["id"];
    }

    private function loadTranslatedIds($originalId = false, $slug = null)
    {
        global $tsm;
        $this->currentSlug = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        foreach ($this->originalsIds as $key => $id) {
            if ($originalId !== false && $originalId !== $id) {
                continue;
            }
            if (isset($this->translatedIds[$id]) && $this->translatedIds[$id]["id"] !== $id) {
                continue;
            }
            $translations = TranslationPost::findTranslationFor($id);
            $postId = $translations->getTranslation($this->currentSlug);
            $this->translatedIds[$id] = [
                "id" => empty($postId) ? $id : $postId,
                "name" => $key
            ];
        }
    }

    public function injectIntoPllUrlTranslated($url, $slug)
    {
        global $tsm;
        if (is_shop()) {
            $name = "shop_page";
        } else if (is_cart()) {
            $name = "cart_page";
        } else if (is_checkout()) {
            $name = "checkout_page";
        } else if (is_account_page()) {
            $name = "myaccount_page";
        } else if (is_page('terms')) {
            $name = "terms_page";
        } else {
            return $url;
        }

        $translations = TranslationPost::findTranslationFor($this->originalsIds[$name]);
        $translatedPostId = $translations->getTranslation($slug);
        if (!$translatedPostId) {
            return $url;
        }

        $translatedUri = get_page_uri($translatedPostId);
        $homeUrl = $tsm->getPolylangManager()->getHomeUrl($slug);
        return $homeUrl . $translatedUri;
    }

    public function redirectToRightUrl()
    {
        // if current URL is not the good one redirect to the good one
        if (is_admin() || is_404() || !(is_product() && !is_shop() && !is_product_category())) {
            return;
        }
        global $tsm;
        $currentSlug = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $currentId = get_the_ID();
        if (!$currentId) {
            return;
        }
        $language = LanguagePost::getLanguage($currentId);
        if ($language["code"] === $currentSlug) {
            return;
        }
        $homeUrl = $tsm->getPolylangManager()->getHomeUrl($currentSlug);
        $currentURL = $_SERVER['REQUEST_URI'];
        $goodUrl = $homeUrl . $language["code"] . $currentURL;
        if ($currentURL !== $goodUrl) {
            wp_redirect($goodUrl, 301);
        }
    }

    public function getPagesQueryArgs($args, $r)
    {
        if (!is_admin() || !isset($_GET["page"]) || $_GET["page"] !== "wc-settings") {
            return $args;
        }
        $defaultLanguage = Language::getDefaultLanguage();
        if (empty($defaultLanguage["code"])) {
            return $args;
        }
        $args["lang"] = $defaultLanguage["code"];
        return $args;
    }

    public function filterFoundPages($pages)
    {
        $defaultLanguage = Language::getDefaultLanguage();
        if (empty($defaultLanguage["code"])) {
            return $pages;
        }

        $response = [];
        foreach ($pages as $pageId => $value) {
            $language = LanguagePost::getLanguage($pageId);
            if (!$language || $language["code"] !== $defaultLanguage["code"]) {
                continue;
            }
            $response[$pageId] = $value;
        }

        return $response;
    }
}

$Options = new Options();
$Options->init();