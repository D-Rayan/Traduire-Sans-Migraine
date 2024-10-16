<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;

class OnPublishedPosts
{

    public function init()
    {
        add_filter('publish_post', [$this, 'handlePublication'], 10, 2);
    }

    public function handlePublication($ID, $post)
    {
        global $tsm;
        $translations = $tsm->getPolylangManager()->getAllTranslationsPost($ID);
        $currentSlug = $tsm->getPolylangManager()->getLanguageSlugForPost($ID);

        foreach ($translations as $translation) {
            if ($translation["code"] === $currentSlug || empty($translation["postId"])) {
                continue;
            }
            DAOInternalsLinks::setToBeFixed($translation["postId"], $currentSlug);
        }
    }
}

$OnPublishedPosts = new OnPublishedPosts();
$OnPublishedPosts->init();