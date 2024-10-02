<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\SeoSansMigraine\Client;

class OfflineProcess {

    private $clientSeoSansMigraine;

    public function __construct()
    {
        $this->clientSeoSansMigraine = Client::getInstance();
    }

    public function addBackgroundProcess() {
        $translations = $this->clientSeoSansMigraine->fetchAllFinishedTranslations();
        foreach ($translations as $translation) {
            $tokenId = $translation["tokenId"];
            $translationData = $translation["translation"];
            $codeTo = $translation["codeTo"];
            $TranslationHelper = new TranslateHelper($tokenId, $translationData, $codeTo);
            $TranslationHelper->handleTranslationResult();
        }
    }
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function init() {
        $key = "_seo_sans_migraine_backgroundProcess";
        $instance = self::getInstance();
        if (get_option($key) === "offline") {
            add_action(wp_doing_ajax() ? "fetchTranslationsBackground" : "admin_init", [$instance, "addBackgroundProcess"]);
        }
    }
}