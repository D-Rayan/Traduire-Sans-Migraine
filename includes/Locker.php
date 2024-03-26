<?php

namespace TraduireSansMigraine;

class Locker {
    private $lockerName;
    private $randomIdKey;

    private $lockerExpiration;

    private $lockerSleepingTime;
    public function __construct($lockerName, $lockerExpiration = 5) {
        $this->lockerName = "traduire_sans_migraine_locker_" . $lockerName;
        $this->randomIdKey = uniqid();
        $this->lockerExpiration = $lockerExpiration;
        $this->lockerSleepingTime = $this->lockerExpiration * 100;
    }

    private function getLockerValue() {
        return get_transient($this->lockerName);
    }

    public function lock() {
        while ($this->isLockedByAnotherProcess()) {
            usleep($this->lockerSleepingTime);
        }
        set_transient($this->lockerName, $this->randomIdKey, $this->lockerExpiration);
        usleep($this->lockerSleepingTime);
        if ($this->isLockedByAnotherProcess()) {
            $this->lock();
        }
    }

    public function unlock() {
        delete_transient($this->lockerName);
    }

    private function isLockedByAnotherProcess() {
        $currentLockerValue = $this->getLockerValue();
        return (!empty($currentLockerValue) && $currentLockerValue !== $this->randomIdKey);
    }
}