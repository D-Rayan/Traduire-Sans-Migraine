<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Attributes;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractPrepareTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends AbstractPrepareTranslation
{
    public function prepareDataToTranslate()
    {
        $this->dataToTranslate = [
            "label" => $this->object->name,
        ];
    }

    protected function getSlugOrigin()
    {
        $language = Language::getDefaultLanguage();

        return $language["code"];
    }

}