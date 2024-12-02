<?php

namespace TraduireSansMigraine\Wordpress\Translatable\AbstractClass;

use Exception;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable;

abstract class AbstractOnCreateAction
{
    /**
     * @var AbstractAction $action
     */
    protected $action;
    protected $children = [];

    public function __construct($action)
    {
        $this->action = $action;
    }

    /**
     * @param AbstractAction $action
     * @return AbstractOnCreateAction
     */
    public static function getInstance($action)
    {
        if ($action->getActionType() === DAOActions::$ACTION_TYPE["EMAIL"]) {
            return new Translatable\Emails\OnCreateAction($action);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            return new Translatable\ElementorModel\OnCreateAction($action);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["TERMS"]) {
            return new Translatable\Terms\OnCreateAction($action);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["ATTRIBUTES"]) {
            return new Translatable\Attributes\OnCreateAction($action);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["PRODUCT"]) {
            return new Translatable\Products\OnCreateAction($action);
        }

        return new Translatable\Posts\OnCreateAction($action);
    }

    public function getChildren()
    {
        $this->prepareChildren();
        return $this->children;
    }

    protected abstract function prepareChildren();

    protected function addChildren($objectId, $actionType)
    {
        if (!in_array($actionType, DAOActions::$ACTION_TYPE)) {
            throw new Exception("Invalid action type");
        }
        $this->children[] = [
            "objectId" => $objectId,
            "actionType" => $actionType
        ];
    }
}