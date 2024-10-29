<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;

class SetDefaultLanguage
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('tsm-set-default-language', [$this, 'setDefaultLanguage']);
        add_action('traduire-sans-migraine_enable_woocommerce', [$this, 'activationWoocommerce']);
    }

    public function activationWoocommerce()
    {
        $code = Language::getDefaultLanguage()["code"];
        if (empty($code)) {
            return;
        }
        $this->setDefaultLanguage($code);
    }

    public function setDefaultLanguage($defaultCode)
    {
        // @todo : optimize to get product WITHOUT language
        $products = get_posts([
            'post_type' => 'product',
            'posts_per_page' => -1,
        ]);
        foreach ($products as $product) {
            $product_id = $product->ID;
            if (!empty(LanguagePost::getLanguage($product_id))) {
                continue;
            }
            LanguagePost::setLanguage($product_id, $defaultCode);
        }
    }
}

$SetDefaultLanguage = new SetDefaultLanguage();
$SetDefaultLanguage->init();
