<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts\ElementorModel;

use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends Translatable\Posts\PrepareTranslation
{
    public function prepareDataToTranslate()
    {
        parent::prepareDataToTranslate();
    }
}