<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Attributes;


use Exception;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractApplyTranslation;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends AbstractApplyTranslation
{
    public function __construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        $translations = TranslationAttribute::findTranslationFor($this->originalObject->id);
        $translations->addTranslation($this->codeTo, $this->dataToTranslate["label"])->save();
    }

    protected function getTranslatedId()
    {
        return $this->originalObject->id;
    }

    protected function getCodeFrom()
    {
        $language = Language::getDefaultLanguage();
        if (empty($language)) {
            throw new Exception("Language not found");
        }
        return $language["code"];
    }
}