<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;

class Action extends AbstractAction
{

    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["TERMS"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        $this->object = get_term($this->getObjectId());
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