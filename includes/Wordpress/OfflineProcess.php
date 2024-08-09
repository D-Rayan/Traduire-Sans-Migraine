<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Front\Pages\Menu\Bulk\Bulk;
use TraduireSansMigraine\Front\Pages\Menu\Products\Products;
use TraduireSansMigraine\Front\Pages\Menu\Settings\Settings;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\Hooks\RestAPI;

class OfflineProcess {

    private $clientSeoSansMigraine;

    public function __construct()
    {
        $this->clientSeoSansMigraine = Client::getInstance();
    }
    public function init() {
        $key = "_seo_sans_migraine_backgroundProcess";
        if (get_option($key) === "offline") {
            add_action("admin_init", [$this, "addBackgroundProcess"]);
        }
    }

    public function addBackgroundProcess() {
        $result = $this->clientSeoSansMigraine->checkCredential();
        if (!$result) {
            return;
        }
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
            self::$instance = new OfflineProcess();
        }
        return self::$instance;
    }
}