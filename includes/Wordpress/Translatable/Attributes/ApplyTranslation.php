<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Attributes;


use TraduireSansMigraine\Wordpress\AbstractClass\AbstractApplyTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;

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
        $translations = TranslationAttribute::findTranslationFor($this->originalObject->attribute_id);
        $translations->addTranslation($this->codeTo, $this->dataToTranslate["label"])->save();
    }

    protected function getTranslatedId()
    {
        return $this->originalObject->attribute_id;
    }
}