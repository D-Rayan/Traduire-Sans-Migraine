<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractPrepareTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
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
        $this->addTermParent($term);
    }

    private function addTermParent($term)
    {
        if (!$term->parent) {
            return;
        }
        $translations = TranslationTerms::findTranslationFor($term->parent);
        if (empty($translations->getTranslation($this->codeTo))) {
            $parent = get_term($term->parent);
            $this->dataToTranslate["term_" . $term->parent . "_name"] = $parent->name;
            $this->dataToTranslate["term_" . $term->parent . "_description"] = $parent->description;
            $this->dataToTranslate["term_" . $term->parent . "_slug"] = $parent->slug;
            $this->addTermParent($parent);
        }
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