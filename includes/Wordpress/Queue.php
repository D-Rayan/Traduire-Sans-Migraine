<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Wordpress\DAO\DAOQueue;
use TraduireSansMigraine\Wordpress\Hooks\PrepareTranslation;
use TraduireSansMigraine\Wordpress\Hooks\StartTranslation;

class Queue {
    private $nextItem;
    public function __construct() {}

    // item should be in format array with ID
    public function isFromQueue($postId, $slugTo) {
        return DAOQueue::getItemByPostId($postId, $slugTo) !== null;
    }

    public function getNextItem() {
        if (null === $this->nextItem) {
            $this->nextItem = DAOQueue::getNextItem();
        }
        return $this->nextItem;
    }

    public function invalidateNextItem() {
        $this->nextItem = null;
    }

    public function isAlreadyInQueue($postId, $slugTo) {
        return DAOQueue::getItemByPostId($postId, $slugTo) !== null;
    }

    public function startNextProcess() {
        $nextItem = $this->getNextItem();
        if ($this->getState() !== "idle" || null === $nextItem) {
            return false;
        }
        DAOQueue::setItemAsProcessing($nextItem->ID);
        $result = PrepareTranslation::getInstance()->prepareTranslationExecute($nextItem->ID, [$nextItem->slugTo]);
        if (!$result["success"]) {
            if (isset($result["data"]["error"]) && $result["data"]["error"] === "loginRequired") {
                DAOQueue::setItemAsPause($nextItem->ID, $result["data"]);
                return false;
            }
            DAOQueue::setItemAsError($nextItem->ID, $result["data"]);
            $this->invalidateNextItem();
            return $this->startNextProcess();
        }
        $post = get_post($nextItem["ID"]);
        $result = StartTranslation::getInstance()->startTranslateExecute($post, $nextItem["languageTo"]);

        if (!$result["success"]) {
            if (isset($result["data"]["reachedMaxQuota"]) || (isset($result["data"]["error"]) && $result["data"]["error"] === "loginRequired")) {
                DAOQueue::setItemAsPause($nextItem->ID, $result["data"]);
            } else if (isset($result["data"]["reachedMaxLanguages"])) {
                DAOQueue::setItemAsError($nextItem->ID, $result["data"]);
            } else {
                DAOQueue::setItemAsError($nextItem->ID, $result["data"]);
            }
            $this->invalidateNextItem();
            return $this->startNextProcess();
        }
        DAOQueue::setItemAsDone($nextItem->ID, $result["data"]);
        $this->invalidateNextItem();
        return true;
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function getState() {
        $nextItem = $this->getNextItem();
        if (null === $nextItem) {
            return DAOQueue::$STATE["PENDING"];
        }
        return $nextItem->state;
    }

    public function addToQueue($postId, $languageTo) {
        return DAOQueue::addToQueue($postId, $languageTo, DAOQueue::$ORIGINS["QUEUE"]);
    }
}