<?php

namespace TraduireSansMigraine\Wordpress\Translatable\ElementorModel;


use TraduireSansMigraine\Wordpress\AbstractClass\AbstractApplyTranslation;

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
        // @TODO: Implement processTranslation() method.
    }
}