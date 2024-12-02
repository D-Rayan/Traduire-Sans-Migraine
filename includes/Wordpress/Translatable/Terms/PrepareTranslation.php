<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractPrepareTranslation;
use WP_Term;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends AbstractPrepareTranslation
{
    public function prepareDataToTranslate()
    {
        /**
         * @var $term WP_Term
         */
        $term = $this->object;
        $this->dataToTranslate = [
            "name" => $term->name,
            "description" => $term->description,
            "slug" => $term->slug,
        ];
    }

    protected function getSlugOrigin()
    {
        $language = LanguageTerm::getLanguage($this->object->term_id);
        if (!empty($language)) {
            return $language["code"];
        }
        $defaultLanguage = Language::getDefaultLanguage();
        if (empty($defaultLanguage["code"])) {
            return null;
        }
        LanguageTerm::setLanguage($this->object->term_id, $defaultLanguage);
        return $defaultLanguage["code"];
    }
}