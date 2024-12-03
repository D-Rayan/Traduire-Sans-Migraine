<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts\ElementorModel;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;

class Action extends AbstractAction
{
    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        $this->object = get_post($this->getObjectId());
    }

    protected function getApplyTranslationInstance($data)
    {
        return new ApplyTranslation($this, $data);
    }
}