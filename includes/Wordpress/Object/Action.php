<?php

namespace TraduireSansMigraine\Wordpress\Object;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\StartTranslation;

class Action
{
    private $ID;
    private $postId;
    private $tokenId;
    private $slugTo;
    private $state;
    private $origin;
    private $response;
    private $createdAt;
    private $updatedAt;
    private $lock;

    private $originalAction;

    public function __construct($args, $isCopy = false)
    {
        $this->postId = $args["postId"];
        $this->slugTo = $args["slugTo"];
        $this->origin = $args["origin"];
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
        if (!$isCopy) {
            $this->originalAction = new Action($this->toArray(), true);
        }
    }

    public function toArray($forClient = false)
    {
        $data = [
            "postId" => $this->getPostId(),
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

        return $data;
    }

    /**
     * @return string
     */
    public function getPostId()
    {
        return $this->postId;
    }

    /**
     * @param string $postId
     */
    public function setPostId($postId)
    {
        $this->postId = $postId;

        return $this;
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

    public static function loadByToken($tokenId)
    {
        $args = DAOActions::getActionByToken($tokenId);
        return empty($args) ? null : new Action($args);
    }

    public static function loadByPostId($postId, $slugTo)
    {
        $args = DAOActions::getActionByPostId($postId, $slugTo);
        return empty($args) ? null : new Action($args);
    }

    public static function getActionPaused()
    {
        $args = DAOActions::getActionPaused();
        return empty($args) ? null : new Action($args);
    }

    public static function getActionsByPostId($postId)
    {
        $actions = DAOActions::getActionsByPostId($postId);
        return array_map(function ($action) {
            return new Action($action);
        }, $actions);
    }

    public function isFromQueue()
    {
        return $this->getOrigin() == DAOActions::$ORIGINS["QUEUE"];
    }

    public function startTranslation()
    {
        // required ?
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

    public function willBeProcessing()
    {
        return in_array($this->getState(), [DAOActions::$STATE["PROCESSING"], DAOActions::$STATE["PAUSE"], DAOActions::$STATE["PENDING"]]);
    }

    public function execute()
    {
        if ($this->getState() !== DAOActions::$STATE["PENDING"]) {
            return;
        }
        $this
            ->addLock()
            ->setAsProcessing()
            ->save();
        $post = get_post($this->getPostId());
        if (!$post) {
            $this->releaseLock()->setAsError()->setResponse(["error" => "postNotFound"])->save();
            return;
        }
        if (!$this->isValidLock()) {
            return;
        }
        $result = StartTranslation::getInstance()->startTranslateExecute($post, $this->getSlugTo());
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

    public function save()
    {
        if ($this->ID) {
            $args = $this->getUpdatedData();
            DAOActions::updateAction($this->ID, $args);
            if (isset($args["state"]) && in_array($this->getState(), [DAOActions::$STATE["DONE"], DAOActions::$STATE["ERROR"], DAOActions::$STATE["ARCHIVED"]])) {
                Queue::getInstance()->startNextProcess();
            } else if (isset($args["state"]) && $args["state"] === DAOActions::$STATE["PENDING"] && $args["lock"] === null) {
                Queue::getInstance()->startNextProcess();
            }
        } else {
            if (Queue::getInstance()->isQueueDone() && $this->getOrigin() == DAOActions::$ORIGINS["QUEUE"]) {
                Queue::getInstance()->setAsArchived();
            }
            $this->ID = DAOActions::createAction($this->postId, $this->slugTo, $this->origin);
            Queue::getInstance()->startNextProcess();
        }
        $this->originalAction = new Action($this->toArray(), true);

        return $this;
    }

    private function getUpdatedData()
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
        if ($this->getLock() !== $this->getOriginalAction()->getLock()) {
            $data["lock"] = $this->getLock();
        }
        return $data;
    }

    private function getOriginalAction()
    {
        return $this->originalAction;
    }

    public function getLock()
    {
        return $this->lock;
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
        $this->lock = uniqid();
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

    private function isValidLock()
    {
        usleep(100);
        $action = Action::loadById($this->getID());
        if ($action->getLock() !== $this->getLock()) {
            return false;
        }
        return true;
    }

    public static function loadById($id)
    {
        $args = DAOActions::get($id);
        return empty($args) ? null : new Action($args);
    }

    public function setAsPause()
    {
        $this->setState(DAOActions::$STATE["PAUSE"]);
        return $this;
    }

    public function isQuotaIssue()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["reachedMaxQuota"]);
    }

    public function getEstimatedQuota()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["estimatedQuota"]) ? $this->response["estimatedQuota"] : PHP_INT_MAX;
    }

    public function isLanguagesIssue()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["reachedMaxLanguages"]);
    }

    public function isLoginRequired()
    {
        return !empty($this->response) && is_array($this->response) && isset($this->response["loginRequired"]);
    }
}