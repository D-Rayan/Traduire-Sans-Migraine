<?php

namespace TraduireSansMigraine\Wordpress\Translatable\ElementorModel;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;

class Action extends AbstractAction
{
    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        // TODO: Implement getObject() method.
    }

    protected function getApplyTranslationInstance($data)
    {
        return new ApplyTranslation($this, $data);
    }
}