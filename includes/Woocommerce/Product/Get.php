<?php

namespace TraduireSansMigraine\Woocommerce\Product;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class Get
{
    public function __construct()
    {

    }

    public function init()
    {
        add_filter("pll_translation_url", [$this, "injectIntoPllUrlTranslated"], 10, 2);
    }

    public function injectIntoPllUrlTranslated($url, $slug)
    {
        global $tsm;
        if (!is_product()) {
            return $url;
        }
        if ($tsm->getPolylangManager()->getCurrentLanguageSlug() === $slug) {
            return $url;
        }
        $post = get_queried_object();
        $postId = $post->ID;
        $translations = TranslationPost::findTranslationFor($postId);
        if (empty($translations->getTranslation($slug))) {
            return $url;
        }
        $translatedPostId = $translations->getTranslation($slug);
        $urlSlug = $tsm->getPolylangManager()->getHomeUrl($slug);
        $urlOriginalSlug = $tsm->getPolylangManager()->getHomeUrl($tsm->getPolylangManager()->getCurrentLanguageSlug());
        return str_replace($urlOriginalSlug, $urlSlug, get_post_permalink($translatedPostId));
    }
}

$Get = new Get();
$Get->init();