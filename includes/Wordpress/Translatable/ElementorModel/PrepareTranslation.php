<?php

namespace TraduireSansMigraine\Wordpress\Translatable\ElementorModel;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractPrepareTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends AbstractPrepareTranslation
{
    public function prepareDataToTranslate()
    {
        // @TODO: Implement prepareDataToTranslate() method.
    }

    protected function getSlugOrigin()
    {
        $language = Language::getDefaultLanguage();

        return $language["code"];
    }
}