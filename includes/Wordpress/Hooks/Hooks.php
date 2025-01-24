<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

if (!defined("ABSPATH")) {
    exit;
}

class Hooks
{

    public function __construct()
    {
    }

    public static function init()
    {
        $instance = self::getInstance();
        $instance->loadHooks();
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function loadHooks()
    {
        $base = __DIR__ . "/";
        require_once $base . "CronInitializeInternalLinks.php";
        require_once $base . "GetAccount.php";
        require_once $base . "ResetInternalsLinksState.php";
        require_once $base . "GetInternalsLinksState.php";
        require_once $base . "UpdateSettings.php";
        require_once $base . "GetVariables.php";
        require_once $base . "CronFixedInternalLinks.php";
        require_once $base . "GetSettings.php";
        require_once $base . "GetProducts.php";
        require_once $base . "SendReasonsDeactivate.php";
        require_once $base . "DebugHelper.php";
        require_once $base . "Actions/RemoveFromQueue.php";
        require_once $base . "Actions/GetActionsByObject.php";
        require_once $base . "Actions/AddToQueue.php";
        require_once $base . "Actions/GetQueue.php";
        require_once $base . "Languages/DeleteWordToDictionary.php";
        require_once $base . "Languages/UpdateLanguageSettings.php";
        require_once $base . "Languages/GetLanguages.php";
        require_once $base . "Languages/UpdateWordToDictionary.php";
        require_once $base . "Languages/SetLanguage.php";
        require_once $base . "Languages/GetDictionary.php";
        require_once $base . "Languages/AddWordToDictionary.php";
        require_once $base . "Languages/AddNewLanguage.php";
        require_once $base . "Objects/GetObjectEstimatedQuota.php";
        require_once $base . "Objects/GetObjects.php";
        require_once $base . "Objects/GetObjectsType.php";
        require_once $base . "Posts/GetPost.php";
        require_once $base . "Posts/OnDeletionPosts.php";
        require_once $base . "Posts/OnPublishedPosts.php";
        require_once $base . "Posts/GetAuthors.php";
        require_once $base . "Woocommerce/GetStateWoocommerce.php";
        require_once $base . "Woocommerce/HandleNewDefaultLanguage.php";
        require_once $base . "Woocommerce/TranslateBulk.php";
    }

    private function rglob($pattern, $flags = 0)
    {
        $files = glob($pattern, $flags);
        foreach (glob(dirname($pattern) . '/*', GLOB_ONLYDIR | GLOB_NOSORT) as $dir) {
            $files = array_merge(
                [], $files, $this->rglob($dir . "/" . basename($pattern), $flags)
            );
        }
        return $files;
    }
}