<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;

class Queue {
    private $nextAction;
    public function __construct() {}

    private function getNextAction() {
        if (!$this->nextAction) {
            $data = DAOActions::getNextOrCurrentAction();
            if (!$data) {
                return null;
            }
            $this->nextAction = new Action($data);
        }
        return $this->nextAction;
    }

    public function startNextProcess() {
        $nextAction = $this->getNextAction();
        if ($this->getState() === DAOActions::$STATE["PAUSE"]) {
            $this->checkIfPauseResolved();
            return;
        }
        if ($this->getState() !== DAOActions::$STATE["PENDING"]) {
            if (!empty($nextAction->getLock()) && strtotime($nextAction->getUpdatedAt()) < strtotime("-15 seconds")) {
                $nextAction->releaseLock()->setAsPending()->save();
                $this->startNextProcess();
            }
            return;
        }
        if ($nextAction === null) {
            return;
        }
        $nextAction->execute();
    }

    public function getActionsEnriched() {
        $queue = DAOActions::getActionsForQueue();
        foreach ($queue as $key => $item) {
            $translationMap = !empty($item["translationMap"]) ? unserialize($item["translationMap"]) : [];
            $slug = $item["slugTo"];
            $translationId = isset($translationMap[$slug]) ? $translationMap[$slug] : null;
            $translation = $translationId ? get_post($translationId) : null;
            $isTranslated = !empty($translation);
            $translationIsUpdated = $translation && $translation->post_modified > $item["post_modified"];
            $translationMap[$slug] = [
                "translation" => $isTranslated ? [
                    "ID" => $translation->ID,
                    "title" => $translation->post_title,
                ] : null,
                "translationIsUpdated" => $translationIsUpdated,
            ];
            $queue[$key]["post"] = [
                "ID" => $item["postId"],
                "post_title" => $item["post_title"],
                "post_author" => $item["post_author"],
                "post_status" => $item["post_status"],
                "translationMap" => $translationMap,
            ];
            $queue[$key]["response"] = empty($item["response"]) ? [] : json_decode($item["response"], true);
            unset($queue[$key]["postId"]);
            unset($queue[$key]["post_title"]);
            unset($queue[$key]["post_author"]);
            unset($queue[$key]["post_status"]);
            unset($queue[$key]["translationMap"]);
        }
        return $queue;
    }

    public function isQueueDone() {
        $nextAction = $this->getNextAction();
        return $nextAction === null;
    }
    public function setAsArchived() {
        DAOActions::setAsArchivedAllDoneActions();
    }

    public function checkIfPauseResolved() {
        global $tsm;
        $nextAction = $this->getNextAction();
        if ($nextAction === null) {
            return;
        }
        if ($nextAction->getState() !== DAOActions::$STATE["PAUSE"]) {
            return;
        }
        if ($nextAction->isLoginRequired() && $tsm->getClient()->checkCredential()) {
            $nextAction->setResponse([])->setAsPending()->save();
        }
        if ($nextAction->isLanguagesIssue() && $tsm->getClient()->fetchAccount()) {
            $account = $tsm->getClient()->getAccount();
            $maxSlugs = $account["slugs"]["max"];
            $currentSlugs = $account["slugs"]["current"];
            $allowedSlugs = $account["slugs"]["allowed"];
            if ($maxSlugs > $currentSlugs || in_array($nextAction->getSlugTo(), $allowedSlugs)) {
                $nextAction->setResponse([])->setAsPending()->save();
            }
        }
        if ($nextAction->isQuotaIssue() && $tsm->getClient()->fetchAccount()) {
            $account = $tsm->getClient()->getAccount();
            $quotaLeft = $account["quota"]["max"] == -1 || $account["quota"]["bonus"] == -1 ? PHP_INT_MAX : $account["quota"]["max"] + $account["quota"]["bonus"] - $account["quota"]["current"];
            if ($quotaLeft >= $nextAction->getEstimatedQuota()) {
                $nextAction->setResponse([])->setAsPending()->save();
            }
        }
    }

    public static function getInstance(): Queue {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    private function getState() {
        $nextAction = $this->getNextAction();
        if (null === $nextAction) {
            return DAOActions::$STATE["PENDING"];
        }
        return $nextAction->getState();
    }

    public static function init() {
        $instance = self::getInstance();
        add_action(wp_doing_ajax() ? "startNextProcess" : "admin_init", [$instance, "startNextProcess"]);
    }
}