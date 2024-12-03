<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts\ElementorModel;

use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class OnCreateAction extends Translatable\Posts\OnCreateAction
{
    protected function prepareChildren()
    {
        parent::prepareChildren();
    }
}