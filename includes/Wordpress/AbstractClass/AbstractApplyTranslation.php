<?php

namespace TraduireSansMigraine\Wordpress\AbstractClass;

use Exception;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

abstract class AbstractApplyTranslation
{
    protected $dataToTranslate;
    protected $codeTo;
    protected $codeFrom;
    protected $originalObject;

    /**
     * @var $action AbstractAction
     */
    protected $action;

    public function __construct($action, $translationData)
    {
        $this->dataToTranslate = $translationData;
        $this->codeTo = $action->getSlugTo();
        $this->action = $action;
    }

    public static function getInstance($tokenId, $translationData)
    {
        $action = AbstractAction::loadByToken($tokenId);
        if (!$action) {
            return null;
        }
        if ($action->getActionType() === DAOActions::$ACTION_TYPE["EMAIL"]) {
            return new Translatable\Emails\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            return new Translatable\ElementorModel\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["TERMS"]) {
            return new Translatable\Terms\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["ATTRIBUTES"]) {
            return new Translatable\Attributes\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["PRODUCT"]) {
            return new Translatable\Products\ApplyTranslation($action, $translationData);
        }

        return new Translatable\Posts\ApplyTranslation($action, $translationData);
    }

    public function applyTranslation()
    {
        if (!$this->action) {
            return false;
        }
        $this->checkRequirements();
        if ($this->action->getState() !== DAOActions::$STATE["PROCESSING"]) {
            return false;
        }
        try {
            $childrenActions = $this->action->getChildren();
            foreach ($childrenActions as $childAction) {
                $childAction->setAsProcessing()->applyTranslation($this->dataToTranslate);
            }
            $this->processTranslation();
            if ($this->action->isFromQueue()) {
                $this->action->setAsDone();
            } else {
                $this->action->setAsArchived();
            }
            $this->action->setObjectIdTranslated($this->getTranslatedId())->save();
        } catch (Exception $e) {
            $this->action->setAsError()->setResponse(["error" => $e->getMessage() ?? $e->getCode()])->save();
        }

        return $this->isSuccess();
    }

    protected function checkRequirements()
    {
        if (!$this->action) {
            return;
        }
        if ($this->dataToTranslate === false) {
            $this->action->setAsError()->setResponse(["error" => "Data to translate not found"])->save();
            return;
        }
        $this->originalObject = $this->action->getObject();
        if (!$this->originalObject) {
            $this->action->setAsError()->setResponse(["error" => "Object not found"])->save();
            return;
        }
        $this->action->setResponse(["percentage" => 75])->save();
    }

    protected abstract function processTranslation();

    abstract protected function getTranslatedId();

    public function isSuccess()
    {
        return $this->action->getState() === DAOActions::$STATE["DONE"] || $this->action->getState() === DAOActions::$STATE["ARCHIVED"];
    }

    public function getResponse()
    {
        return $this->action->getResponse();
    }

    protected function is_json($string)
    {
        json_decode($string);
        return (json_last_error() == JSON_ERROR_NONE);
    }

    protected function is_serialized($string)
    {
        return ($string == serialize(false) || @unserialize($string) !== false);
    }
}