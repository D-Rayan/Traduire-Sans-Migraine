<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;

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
        $this->addTermParent();
    }

    private function addTermParent()
    {
        $term = $this->action->getObject();
        if (!$term->parent) {
            return;
        }
        $translations = TranslationTerms::findTranslationFor($term->parent);
        if (!empty($translations->getTranslation($this->action->getSlugTo()))) {
            return;
        }
        $this->addChildren($term->parent, DAOActions::$ACTION_TYPE["TERMS"]);
    }
}