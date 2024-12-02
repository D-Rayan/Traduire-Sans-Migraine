<?php

namespace TraduireSansMigraine\Wordpress\Translatable\AbstractClass;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\Translatable;

abstract class AbstractAction
{
    protected $ID;
    protected $objectId;
    protected $tokenId;
    protected $slugTo;
    protected $state;
    protected $origin;
    protected $response;
    protected $createdAt;
    protected $updatedAt;
    protected $lock;
    protected $object;

    protected $estimatedQuota;

    protected $originalAction;
    protected $dataToTranslate;
    protected $actionType;
    protected $actionParent;
    protected $objectIdTranslated;

    /**
     * @var AbstractAction[]
     */
    protected $children;

    public function __construct($args, $isCopy = false)
    {
        $this->objectId = $args["objectId"];
        $this->slugTo = $args["slugTo"];
        $this->origin = $args["origin"];
        if (isset($args["actionType"])) {
            $this->actionType = $args["actionType"];
        }
        if (isset($args["ID"])) {
            $this->ID = $args["ID"];
        }
        if (isset($args["tokenId"])) {
            $this->tokenId = $args["tokenId"];
        }
        if (isset($args["state"])) {
            $this->state = $args["state"];
        }
        if (isset($args["response"])) {
            $this->response = json_decode($args["response"], true);
        } else {
            $this->response = [];
        }
        if (isset($args["createdAt"])) {
            $this->createdAt = $args["createdAt"];
        } else {
            $this->createdAt = date('Y-m-d') . 'T' . date('H:i:s') . 'Z';
        }
        if (isset($args["updatedAt"])) {
            $this->updatedAt = $args["updatedAt"];
        } else {
            $this->updatedAt = date('Y-m-d') . 'T' . date('H:i:s') . 'Z';
        }
        if (isset($args["lock"])) {
            $this->lock = $args["lock"];
        }
        if (isset($args["estimatedQuota"])) {
            $this->estimatedQuota = $args["estimatedQuota"];
        }
        if (isset($args["actionParent"])) {
            $this->actionParent = $args["actionParent"];
        }
        if (isset($args["objectIdTranslated"])) {
            $this->objectIdTranslated = $args["objectIdTranslated"];
        }
        $this->loadChildren();

        if (!$isCopy) {
            $this->originalAction = self::getInstance($this->toArray(), true);
        }
    }

    private function loadChildren()
    {
        $this->children = [];
        if (!empty($this->ID)) {
            $children = DAOActions::getChildren($this->ID);
            if (!is_array($children)) {
                return;
            }
            foreach ($children as $child) {
                $this->loadChild($child);
            }
        } else {
            $instance = AbstractOnCreateAction::getInstance($this);
            foreach ($instance->getChildren() as $childArgs) {
                $this->addChild($childArgs["objectId"], $childArgs["actionType"]);
            }
        }
    }

    /**
     * @return AbstractAction[]
     */
    public function getChildren()
    {
        return $this->children;
    }

    public function loadChild($data)
    {
        $this->children[] = self::getInstance($data);
    }

    public static function getInstance($args, $isCopy = false)
    {
        if (empty($args)) {
            return null;
        }
        if (!isset($args["actionType"])) {
            return new Translatable\Posts\Action($args, $isCopy);
        }
        if ($args["actionType"] === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            return new Translatable\ElementorModel\Action($args, $isCopy);
        }
        if ($args["actionType"] === DAOActions::$ACTION_TYPE["EMAIL"]) {
            return new Translatable\Emails\Action($args, $isCopy);
        }
        if ($args["actionType"] === DAOActions::$ACTION_TYPE["TERMS"]) {
            return new Translatable\Terms\Action($args, $isCopy);
        }
        if ($args["actionType"] === DAOActions::$ACTION_TYPE["ATTRIBUTES"]) {
            return new Translatable\Attributes\Action($args, $isCopy);
        }
        if ($args["actionType"] === DAOActions::$ACTION_TYPE["PRODUCT"]) {
            return new Translatable\Products\Action($args, $isCopy);
        }
        return new Translatable\Posts\Action($args, $isCopy);
    }

    public function addChild($objectId, $actionType)
    {
        $data = [
            "actionType" => $actionType,
            "objectId" => $objectId,
            "slugTo" => $this->slugTo,
            "origin" => $this->origin,
            "actionParent" => $this->ID,
        ];
        $actionChild = self::getInstance($data);
        if ($this->haveChild($actionChild)) {
            return;
        }
        $this->children[] = $actionChild;
    }

    protected function haveChild($action)
    {
        foreach ($this->children as $child) {
            if ($child->getObjectId() === $action->getObjectId() && $child->getActionType() === $action->getActionType()) {
                return true;
            }
            if ($child->haveChild($action)) {
                return true;
            }
        }
        return false;
    }

    /**
     * @return string
     */
    public function getObjectId()
    {
        return $this->objectId;
    }

    /**
     * @param string $objectId
     */
    public function setObjectId($objectId)
    {
        $this->objectId = $objectId;

        return $this;
    }

    public function getActionType()
    {
        return $this->actionType;
    }

    public function toArray($forClient = false)
    {
        $data = [
            "objectId" => $this->getObjectId(),
            "slugTo" => $this->getSlugTo(),
            "origin" => $this->getOrigin(),
        ];

        if (!empty($this->getID())) {
            $data["ID"] = $this->getID();
        }
        if (!empty($this->getTokenId())) {
            $data["tokenId"] = $this->getTokenId();
        }
        if (!empty($this->getState())) {
            $data["state"] = $this->getState();
        } else {
            $data["state"] = DAOActions::$STATE["PENDING"];
        }
        if (!$forClient) {
            if (!empty($this->getResponse())) {
                $data["response"] = json_encode($this->getResponse());
            } else {
                $data["response"] = json_encode([]);
            }
        } else {
            $data["response"] = $this->getResponse();
        }
        if (!empty($this->getCreatedAt())) {
            $data["createdAt"] = $this->getCreatedAt();
        }
        if (!empty($this->getUpdatedAt())) {
            $data["updatedAt"] = $this->getUpdatedAt();
        }
        if (!empty($this->getEstimatedQuota())) {
            $data["estimatedQuota"] = $this->getEstimatedQuota();
        }
        if (!empty($this->getActionType())) {
            $data["actionType"] = $this->getActionType();
        }
        if (!empty($this->getActionParent())) {
            $data["actionParent"] = $this->getActionParent();
        }

        return $data;
    }

    /**
     * @return string
     */
    public function getSlugTo()
    {
        return $this->slugTo;
    }

    /**
     * @param string $slugTo
     */
    public function setSlugTo($slugTo)
    {
        $this->slugTo = $slugTo;

        return $this;
    }

    /**
     * @return string
     */
    public function getOrigin()
    {
        return $this->origin;
    }

    /**
     * @param string $origin
     */
    public function setOrigin($origin)
    {
        $this->origin = $origin;

        return $this;
    }

    /**
     * @return string
     */
    public function getID()
    {
        return $this->ID;
    }

    /**
     * @param string $ID
     */
    public function setID($ID)
    {
        $this->ID = $ID;

        return $this;
    }

    /**
     * @return string
     */
    public function getTokenId()
    {
        return $this->tokenId;
    }

    /**
     * @param string $tokenId
     */
    public function setTokenId($tokenId)
    {
        $this->tokenId = $tokenId;

        return $this;
    }

    /**
     * @return string
     */
    public function getState()
    {
        return $this->state;
    }

    /**
     * @param string $state
     */
    public function setState($state)
    {
        $this->state = $state;

        return $this;
    }

    /**
     * @return mixed
     */
    public function getResponse()
    {
        return $this->response;
    }

    /**
     * @param mixed $response
     */
    public function setResponse($response)
    {
        $this->response = $response;

        return $this;
    }

    /**
     * @return string
     */
    public function getCreatedAt()
    {
        return $this->createdAt;
    }

    /**
     * @param string $createdAt
     */
    public function setCreatedAt($createdAt)
    {
        $this->createdAt = $createdAt;

        return $this;
    }

    /**
     * @return string
     */
    public function getUpdatedAt()
    {
        return $this->updatedAt;
    }

    /**
     * @param string $updatedAt
     */
    public function setUpdatedAt($updatedAt)
    {
        $this->updatedAt = $updatedAt;

        return $this;
    }

    public function getEstimatedQuota()
    {
        if (!is_int($this->estimatedQuota)) {
            $this->retrieveEstimatedQuota();
        }
        return $this->estimatedQuota;
    }

    public function retrieveEstimatedQuota()
    {
        global $tsm;
        $this->estimatedQuota = 0;
        if (!empty($this->getActionParent())) {
            return;
        }
        $object = $this->getObject();
        if (empty($object)) {
            return;
        }
        $instance = AbstractPrepareTranslation::getInstance($this);
        $this->dataToTranslate = $instance->getDataToTranslate();
        $result = $tsm->getClient()->getEstimatedQuota($this->dataToTranslate, [
            "translateAssets" => $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["translateAssets"])
        ]);
        if (!$result["success"]) {
            return;
        }
        $this->estimatedQuota = $result["data"]["estimatedCredits"];
    }

    public function getActionParent()
    {
        return $this->actionParent;
    }

    public function setActionParent($actionParent)
    {
        $this->actionParent = $actionParent;

        return $this;
    }

    public function getObject()
    {
        if (empty($this->object)) {
            $this->loadObject();
        }
        return $this->object;
    }

    abstract public function loadObject();

    public function getDataToTranslate()
    {
        if (empty($this->dataToTranslate)) {
            $this->prepareDataToTranslate();
        }
        return $this->dataToTranslate;
    }

    public function prepareDataToTranslate()
    {
        $instance = AbstractPrepareTranslation::getInstance($this);
        $data = $instance->getDataToTranslate();

        foreach ($data as $key => $value) {
            $this->dataToTranslate[$this->applyFilterOnKey($key)] = $value;
        }
    }

    private function applyFilterOnKey($key)
    {
        return $this->getPrefixKey() . $key;
    }

    public function getPrefixKey()
    {
        if (!$this->getActionParent()) {
            return "";
        }
        $uniqueId = empty($this->getID()) ? intval(microtime(true) * 1000) : $this->getID();
        return "child_action_" . $uniqueId . "_";
    }

    public static function loadByToken($tokenId)
    {
        $args = DAOActions::getActionByToken($tokenId);
        return AbstractAction::getInstance($args);
    }

    public static function getActionPaused()
    {
        $args = DAOActions::getActionPaused();
        return AbstractAction::getInstance($args);
    }

    public function isFromQueue()
    {
        return $this->getOrigin() == DAOActions::$ORIGINS["QUEUE"];
    }

    public function setAsPending()
    {
        $this->setState(DAOActions::$STATE["PENDING"]);
        return $this;
    }

    public function setAsDone()
    {
        $this->setState(DAOActions::$STATE["DONE"]);
        return $this;
    }

    public function isQuotaIssue()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["reachedMaxQuota"]);
    }

    public function isLanguagesIssue()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["reachedMaxLanguages"]);
    }

    public function isLoginRequired()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["loginRequired"]);
    }

    public function execute()
    {
        if (!$this->canExecute()) {
            return;
        }
        $result = $this->translate();
        $this->endExecute($result);
    }

    protected function canExecute()
    {
        if ($this->getState() !== DAOActions::$STATE["PENDING"]) {
            return false;
        }
        $this
            ->addLock()
            ->setAsProcessing()
            ->save();
        $object = $this->getObject();
        if (!$object) {
            $this->releaseLock()->setAsError()->setResponse(["error" => "objectNotFound"])->save();
            return false;
        }
        if (!$this->isValidLock()) {
            return false;
        }

        return true;
    }

    public function save()
    {
        if (!$this->ID) {
            $alreadyExists = self::loadByObjectId($this->objectId, $this->slugTo, $this->actionType);
            if ($alreadyExists !== null && $alreadyExists->willBeProcessing()) {
                $alreadyExists->setOrigin($this->getOrigin())->save();
                return $alreadyExists;
            }
        }

        if ($this->ID) {
            $args = $this->getUpdatedData();
            DAOActions::updateAction($this->ID, $args);
            $this->saveChildren();
            if (isset($args["state"])) {
                DAOActions::updateStateClonedActionsPending($args["state"], $this->objectId, $this->slugTo);
                DAOActions::updateStateChildrenActions($args["state"], $this->ID);
            }
        } else {
            if (Queue::getInstance()->isQueueDone() && $this->getOrigin() == DAOActions::$ORIGINS["QUEUE"]) {
                Queue::getInstance()->setAsArchived();
            }
            $this->retrieveEstimatedQuota();
            $this->ID = DAOActions::createAction($this->objectId, $this->slugTo, $this->origin, $this->estimatedQuota, $this->actionType, $this->actionParent);
            $this->saveChildren();
            DAOActions::updateAction($this->ID, ["state" => DAOActions::$STATE["PENDING"]]);
        }
        $this->originalAction = AbstractAction::getInstance($this->toArray(), true);

        return $this;
    }

    public static function loadByObjectId($objectId, $slugTo, $actionType = null)
    {
        if ($actionType === null) {
            $actionType = DAOActions::$ACTION_TYPE["POST_PAGE"];
        }
        $args = DAOActions::getActionByObjectId($objectId, $slugTo, $actionType);
        return AbstractAction::getInstance($args);
    }

    public function willBeProcessing()
    {
        return in_array($this->getState(), [DAOActions::$STATE["PROCESSING"], DAOActions::$STATE["PAUSE"], DAOActions::$STATE["PENDING"]]);
    }

    public function getUpdatedData()
    {
        $data = [];
        if ($this->getState() !== $this->getOriginalAction()->getState()) {
            $data["state"] = $this->getState();
        }
        if (json_encode($this->getResponse()) !== json_encode($this->getOriginalAction()->getResponse())) {
            $data["response"] = json_encode($this->getResponse());
        }
        if ($this->getTokenId() !== $this->getOriginalAction()->getTokenId()) {
            $data["tokenId"] = $this->getTokenId();
        }
        if ($this->getObjectIdTranslated() !== $this->getOriginalAction()->getObjectIdTranslated()) {
            $data["objectIdTranslated"] = $this->getObjectIdTranslated();
        }
        if ($this->getLock() !== $this->getOriginalAction()->getLock()) {
            $data["lock"] = $this->getLock();
        }
        return $data;
    }

    protected function getOriginalAction()
    {
        return $this->originalAction;
    }

    public function getObjectIdTranslated()
    {
        return $this->objectIdTranslated;
    }

    public function setObjectIdTranslated($objectIdTranslated)
    {
        $this->objectIdTranslated = $objectIdTranslated;
        return $this;
    }

    public function getLock()
    {
        return $this->lock;
    }

    private function saveChildren()
    {
        foreach ($this->getChildren() as $child) {
            $child->setActionParent($this->ID);
            $child->save();
        }
    }

    public function setAsArchived()
    {
        $this->setState(DAOActions::$STATE["ARCHIVED"]);
        return $this;
    }

    public function setAsProcessing()
    {
        $this->setState(DAOActions::$STATE["PROCESSING"]);
        return $this;
    }

    public function addLock()
    {
        $this->lock = uniqid(rand(0, 1000), true);
        return $this;
    }

    public function setAsError()
    {
        $this->setState(DAOActions::$STATE["ERROR"]);
        return $this;
    }

    public function releaseLock()
    {
        DAOActions::releaseLock($this->ID, $this->lock);
        $this->lock = null;
        return $this;
    }

    public function isValidLock()
    {
        usleep(100);
        $action = AbstractAction::loadById($this->getID());
        if ($action->getLock() !== $this->getLock()) {
            return false;
        }
        return true;
    }

    public static function loadById($id)
    {
        $args = DAOActions::get($id);
        return self::getInstance($args);
    }

    protected function translate()
    {
        $instance = AbstractPrepareTranslation::getInstance($this);
        return $instance->startTranslateExecute();
    }

    protected function endExecute($result)
    {
        $this->releaseLock();
        if (!$result["success"]) {
            if (isset($result["data"]["reachedMaxQuota"]) || (isset($result["data"]["error"]) && $result["data"]["error"] === "loginRequired")) {
                $this->setAsPause()->setResponse($result["data"]);
            } else {
                $this->setAsError()->setResponse($result["data"]);
            }
        } else {
            $this->setTokenId($result["data"]["tokenId"]);
        }
        $this->save();
    }

    public function setAsPause()
    {
        $this->setState(DAOActions::$STATE["PAUSE"]);
        return $this;
    }

    /**
     * @param $data
     * @return Translatable\Posts\ApplyTranslation|Translatable\ElementorModel\ApplyTranslation|Translatable\Emails\ApplyTranslation|Translatable\Terms\ApplyTranslation
     */
    abstract protected function getApplyTranslationInstance($data);
}