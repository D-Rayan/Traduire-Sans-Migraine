<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Wordpress\Hooks\TranslationsHooks;

class Queue {
    private $queue = [];
    const QUEUE_OPTION = "traduire_sans_migraine_queue";
    const QUEUE_OPTION_STATE = "traduire_sans_migraine_queue_state";
    public function __construct() {
        $this->loadQueue();
    }

    private function loadQueue() {
        $this->queue = get_option(self::QUEUE_OPTION, []);
    }

    private function saveQueue() {
        update_option(self::QUEUE_OPTION, $this->queue);
    }
    public function getQueue() {
        return $this->queue;
    }

    public function add($items) {
        $validItems = array_filter($items, function ($item) {
            return isset($item["ID"]);
        });
        $allItemsAreCompleted = true;
        foreach ($this->queue as $item) {
            $allItemsAreCompleted = $allItemsAreCompleted && $item["processed"];
        }
        if ($allItemsAreCompleted) {
            $this->queue = [];
        }
        $this->queue = array_merge($this->queue, $validItems);
        $this->saveQueue();
        if ($this->getState() === "idle") {
            $this->startNextProcess();
        }
    }

    // item should be in format array with ID
    public function isFromQueue($postId) {
        return $this->getFromQueue($postId) !== false;
    }

    public function getFromQueue($postId) {
        foreach ($this->queue as $queueItem) {
            if (isset($queueItem["ID"]) && intval($queueItem)["ID"] === intval($postId)) {
                return $queueItem;
            }
        }
        return false;
    }

    public function remove($item) {
        $this->queue = array_filter($this->queue, function ($queueItem) use ($item) {
            return $queueItem["ID"] !== $item["ID"];
        });
        $this->saveQueue();
    }

    public function updateItem($item) {
        $this->queue = array_map(function ($queueItem) use ($item) {
            if (intval($queueItem["ID"]) !== intval($item["ID"])) {
                return $queueItem;
            }
            return $item;
        }, $this->queue);
        $this->saveQueue();
    }

    public function getNextItem() {
        $validQueue = array_filter($this->queue, function ($item) {
            return isset($item["ID"]) && !isset($item["processed"]);
        });
        $firstItem = reset($validQueue);
        return $firstItem ?: null;
    }

    public function stopQueue() {
        $this->updateState("idle");
    }

    public function startNextProcess() {
        $nextItem = $this->getNextItem();
        if ($this->getState() !== "idle" || null === $nextItem) {
            return false;
        }
        $this->updateState("processing");
        // start Translation
        $result = TranslationsHooks::getInstance()->prepareTranslationExecute($nextItem["ID"], [$nextItem["languageTo"]]);
        if (!$result["success"]) {
            $nextItem["processed"] = true;
            $nextItem["data"] = $result["data"];
            $nextItem["error"] = true;
            $this->updateItem($nextItem);
            $this->updateState("idle");
            return $this->startNextProcess();
        }
        $post = get_post($nextItem["ID"]);
        $result = TranslationsHooks::getInstance()->startTranslateExecute($post, $nextItem["languageTo"]);
        if (!$result["success"]) {
            $nextItem["processed"] = true;
            $nextItem["data"] = $result["data"];
            $nextItem["error"] = true;
            $this->updateItem($nextItem);
            $this->updateState("idle");
            return $this->startNextProcess();
        }
        $nextItem["data"] = $result["data"];
        $this->updateItem($nextItem);
        return true;
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function deleteQueue() {
        delete_option(self::QUEUE_OPTION);
        $this->updateState("idle");
    }

    private function updateState($state) {
        update_option(self::QUEUE_OPTION_STATE, $state);
    }

    public function getState() {
        return get_option(self::QUEUE_OPTION_STATE, "idle");
    }
}