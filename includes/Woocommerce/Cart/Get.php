<?php

namespace TraduireSansMigraine\Woocommerce\Cart;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class Get
{
    public function __construct()
    {

    }

    public function init()
    {
        add_filter('woocommerce_get_cart_contents', [$this, "filterByLanguage"]);

    }

    public function filterByLanguage($cart_contents)
    {
        if (!is_cart() && !is_checkout() && !is_checkout_pay_page()) {
            return $cart_contents;
        }
        global $tsm;
        $currentSlug = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        foreach ($cart_contents as $key => $value) {
            $productLanguage = LanguagePost::getLanguage($value["product_id"]);
            if (!$productLanguage) {
                continue;
            }
            if ($productLanguage["code"] !== $currentSlug) {
                unset($cart_contents[$key]);
            }
        }

        return $cart_contents;
    }
}

$Get = new Get();
$Get->init();