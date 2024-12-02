<?php

namespace TraduireSansMigraine\Wordpress\Translatable\ElementorModel;


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
        // @TODO: Implement processTranslation() method.
    }

    protected function getCodeFrom()
    {
        // @TODO: Implement processTranslation() method.
    }

    protected function getTranslatedId()
    {
        // @TODO: Implement processTranslation() method.
    }
}