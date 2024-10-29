<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;

class OnPublishedPosts
{

    public function init()
    {
        add_filter('publish_post', [$this, 'handlePublication'], 10, 2);
    }

    public function handlePublication($ID, $post)
    {
        $translations = TranslationPost::findTranslationFor($ID);
        $language = LanguagePost::getLanguage($ID);
        $defaultLanguage = LanguagePost::getDefaultLanguage();
        if (empty($language)) {
            return;
        }
        foreach ($translations->getTranslations() as $code => $translatedPostId) {
            if (($code === $language["code"]) || empty($translatedPostId)) {
                continue;
            }
            if ($defaultLanguage && $code === $defaultLanguage["code"]) {
                do_action("tsm-post-published", $translatedPostId, $ID);
            }
            DAOInternalsLinks::setToBeFixed($translatedPostId, $language["code"]);
        }
    }
}

$OnPublishedPosts = new OnPublishedPosts();
$OnPublishedPosts->init();