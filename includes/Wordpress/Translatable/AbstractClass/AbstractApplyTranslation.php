<?php

namespace TraduireSansMigraine\Wordpress\Translatable\AbstractClass;

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
        $this->originalObject = $action->getObject();
        $this->codeFrom = $this->getCodeFrom();
    }

    protected abstract function getCodeFrom();

    public static function getInstance($tokenId, $translationData)
    {
        $action = AbstractAction::loadByToken($tokenId);
        if (!$action) {
            return null;
        }
        return self::getInstanceByAction($action, $translationData);
    }

    private static function getInstanceByAction($action, $translationData)
    {
        if ($action->getActionType() === DAOActions::$ACTION_TYPE["EMAIL"]) {
            return new Translatable\Emails\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            return new Translatable\Posts\ElementorModel\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["TERMS"]) {
            return new Translatable\Terms\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["ATTRIBUTES"]) {
            return new Translatable\Attributes\ApplyTranslation($action, $translationData);
        } else if ($action->getActionType() === DAOActions::$ACTION_TYPE["PRODUCT"]) {
            return new Translatable\Posts\Products\ApplyTranslation($action, $translationData);
        }

        return new Translatable\Posts\ApplyTranslation($action, $translationData);
    }

    public function applyTranslation()
    {
        if (!$this->action) {
            return false;
        }
        $this->checkRequirements();
        if (!is_object($this->action) || $this->action->getState() !== DAOActions::$STATE["PROCESSING"]) {
            return false;
        }
        if (empty($this->action->getActionParent()) && !$this->action->isValidLock()) {
            return false;
        }
        try {
            $childrenActions = $this->action->getChildren();
            foreach ($childrenActions as $childAction) {
                $data = [];
                foreach ($this->dataToTranslate as $key => $value) {
                    if (strpos($key, $childAction->getPrefixKey()) === 0) {
                        $key = str_replace($childAction->getPrefixKey(), "", $key);
                        $data[$key] = $value;
                    }
                }
                $childAction->setAsProcessing()->save();
                $instance = self::getInstanceByAction($childAction, $data);
                $instance->applyTranslation();
            }
            $this->processTranslation();
            if ($this->action->isFromQueue()) {
                $this->action->setAsDone();
            } else {
                $this->action->setAsArchived();
            }
            if (empty($this->action->getActionParent())) {
                $this->action->releaseLock();
            }
            $this->action->setObjectIdTranslated($this->getTranslatedId())->save();
        } catch (Exception $e) {
            if (empty($this->action->getActionParent())) {
                $this->action->releaseLock();
            }
            $this->action->setAsError()->setResponse(["error" => $e->getMessage() ?? $e->getCode()])->save();
        }

        return $this->isSuccess();
    }

    private function checkRequirements()
    {
        if (!$this->action || !is_object($this->action)) {
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
        if (empty($this->action->getActionParent())) {
            $this->action->addLock();
        }
        $this->action->setResponse(["percentage" => 75])->save();
    }

    protected abstract function processTranslation();

    protected abstract function getTranslatedId();

    public function isSuccess()
    {
        return is_object($this->action) && ($this->action->getState() === DAOActions::$STATE["DONE"] || $this->action->getState() === DAOActions::$STATE["ARCHIVED"]);
    }

    public function getResponse()
    {
        return is_object($this->action) ? $this->action->getResponse() : null;
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
