<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;

class Queue
{
    private $nextAction;

    public function __construct()
    {
    }

    public static function init()
    {
        $instance = self::getInstance();
        if (wp_doing_ajax()) {
            add_action("startNextProcess", [$instance, "startNextProcess"]);
        }
    }

    public static function getInstance(): Queue
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function startNextProcess()
    {
        $nextAction = $this->getNextAction();
        if ($this->getState() === DAOActions::$STATE["PAUSE"]) {
            $this->checkIfPauseResolved();
            return;
        }
        if ($this->getState() !== DAOActions::$STATE["PENDING"]) {
            if (!empty($nextAction->getLock()) && strtotime($nextAction->getUpdatedAt()) < strtotime("-5 minutes")) {
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

    private function getNextAction()
    {
        if (!$this->nextAction) {
            $data = DAOActions::getNextOrCurrentAction();
            $this->nextAction = AbstractAction::getInstance($data);
        }
        return $this->nextAction;
    }

    private function getState()
    {
        $nextAction = $this->getNextAction();
        if (null === $nextAction) {
            return DAOActions::$STATE["PENDING"];
        }
        return $nextAction->getState();
    }

    public function checkIfPauseResolved()
    {
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

    public function isQueueDone()
    {
        $nextAction = $this->getNextAction();
        return $nextAction === null;
    }

    public function setAsArchived()
    {
        DAOActions::setAsArchivedAllDoneActions();
    }
}