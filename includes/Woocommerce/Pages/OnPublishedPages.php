<?php

namespace TraduireSansMigraine\Woocommerce\Pages;

class OnPublishedPages
{

    public function init()
    {
        add_action('tsm-post-published', [$this, 'handlePublication'], 10, 2);
        add_action('traduire-sans-migraine_enable_woocommerce', [$this, 'activationWoocommerce']);
    }

    public function activationWoocommerce()
    {
        flush_rewrite_rules();
    }

    public function handlePublication($defaultPageId, $translatedPageId)
    {
        $pages = [
            get_option('woocommerce_shop_page_id'),
            get_option('woocommerce_cart_page_id'),
            get_option('woocommerce_checkout_page_id'),
            get_option('woocommerce_myaccount_page_id'),
            get_option('woocommerce_terms_page_id'),
        ];

        if (!in_array($defaultPageId, $pages)) {
            return;
        }
        flush_rewrite_rules();
    }
}

$OnPublishedPages = new OnPublishedPages();
$OnPublishedPages->init();