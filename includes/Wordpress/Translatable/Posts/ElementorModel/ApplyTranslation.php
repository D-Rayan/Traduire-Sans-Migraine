<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts\ElementorModel;

use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends Translatable\Posts\ApplyTranslation
{
    public function __construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        parent::processTranslation();
    }
}