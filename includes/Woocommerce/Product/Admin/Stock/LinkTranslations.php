<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin\Stock;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class LinkTranslations
{
    public function __construct()
    {

    }

    public function handleUpdateStatusStock($productId, $productStockStatus, $product)
    {
        $this->handleUpdateStock($product);
    }

    public function handleUpdateStock($product)
    {
        $translations = TranslationPost::findTranslationFor($product->get_id());
        if (count($translations->getTranslations()) <= 1) {
            return;
        }
        $traduire_sans_migraine_inventory_linked = get_post_meta($product->get_id(), "traduire_sans_migraine_inventory_linked", true);
        if ($traduire_sans_migraine_inventory_linked != 1) {
            return;
        }
        $this->unregisterHooks();
        foreach ($translations->getTranslations() as $productId) {
            if ($productId == $product->get_id()) {
                continue;
            }
            $isAlsoShared = get_post_meta($productId, "traduire_sans_migraine_inventory_linked", true);
            if ($isAlsoShared != 1) {
                continue;
            }
            $productLinked = wc_get_product($productId);
            if (!$productLinked) {
                continue;
            }
            $productLinked->set_stock_quantity($product->get_stock_quantity());
            $productLinked->set_stock_status($product->get_stock_status());
            $productLinked->save();
        }
        $this->registerHooks();
    }

    public function init()
    {
        $this->registerHooks();
    }

    private function unregisterHooks()
    {
        remove_action('woocommerce_variation_set_stock', [$this, "handleUpdateStock"]);
        remove_action('woocommerce_product_set_stock', [$this, "handleUpdateStock"]);
        remove_action('woocommerce_product_set_stock_status', [$this, "handleUpdateStatusStock"]);
        remove_action('woocommerce_variation_set_stock_status', [$this, "handleUpdateStatusStock"]);
    }

    private function registerHooks()
    {
        add_action('woocommerce_variation_set_stock', [$this, "handleUpdateStock"]);
        add_action('woocommerce_product_set_stock', [$this, "handleUpdateStock"]);
        add_action('woocommerce_product_set_stock_status', [$this, "handleUpdateStatusStock"], 10, 3);
        add_action('woocommerce_variation_set_stock_status', [$this, "handleUpdateStatusStock"], 10, 3);
    }

}

$LinkTranslations = new LinkTranslations();
$LinkTranslations->init();