<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;

class Action extends AbstractAction
{

    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["POST_PAGE"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        $this->object = get_post($this->getObjectId());
    }

    public function toArray($forClient = false)
    {
        $array = parent::toArray($forClient);

        return $array;
    }

    protected function canExecute()
    {
        return parent::canExecute();
    }

    protected function getApplyTranslationInstance($data)
    {
        return new ApplyTranslation($this, $data);
    }
}