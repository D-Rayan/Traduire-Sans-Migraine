<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractOnCreateAction;

if (!defined("ABSPATH")) {
    exit;
}

class OnCreateAction extends AbstractOnCreateAction
{
    protected function prepareChildren()
    {
        $post = $this->action->getObject();
        $this->handleCategories($post);
    }

    private function handleCategories($post)
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["translateCategories"])) {
            return;
        }
        foreach ($post->post_category as $categoryId) {
            $translations = TranslationTerms::findTranslationFor($categoryId);
            if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
                continue;
            }
            $this->addChildren($categoryId, DAOActions::$ACTION_TYPE["TERMS"]);
        }
    }
}