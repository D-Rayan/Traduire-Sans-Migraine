<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractApplyTranslation;

class OfflineProcess
{

    private static $instance = null;
    private $clientSeoSansMigraine;

    public function __construct()
    {
        $this->clientSeoSansMigraine = Client::getInstance();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function init()
    {
        if (!is_admin() && !wp_doing_ajax()) {
            return;
        }
        $key = "_seo_sans_migraine_backgroundProcess";
        $instance = self::getInstance();
        if (get_option($key) === "offline") {
            $page = $_GET["page"] ?? "";
            if (strpos($page, "traduire-sans-migraine") !== false && !wp_doing_ajax()) {
                add_action("admin_init", [$instance, "addBackgroundProcess"]);
            } else {
                add_action("fetchTranslationsBackground", [$instance, "addBackgroundProcess"]);
            }
        } else {
            $action = DAOActions::getNextOrCurrentAction();
            if (!$action || strtotime($action["updatedAt"]) < time() - 60) {
                add_action("fetchTranslationsBackground", [$instance, "addBackgroundProcess"]);
            }
        }
    }

    public function addBackgroundProcess()
    {
        $translations = $this->clientSeoSansMigraine->fetchAllFinishedTranslations();
        foreach ($translations as $translation) {
            $tokenId = $translation["tokenId"];
            $translationData = $translation["translation"];
            $instanceTranslation = AbstractApplyTranslation::getInstance($tokenId, $translationData);
            if (empty($instanceTranslation)) {
                continue;
            }
            $instanceTranslation->applyTranslation();
        }
    }
}