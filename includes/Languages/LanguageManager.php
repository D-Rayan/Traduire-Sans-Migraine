<?php

namespace TraduireSansMigraine\Languages;

use TraduireSansMigraine\SeoSansMigraine\Client;

if (!defined("ABSPATH")) {
    exit;
}
class LanguageManager
{
    /**
     * @var LanguageInterface $manager
     */
    private $manager;
    private $languagesAllowed;

    /**
     * @throws \Exception
     */
    public function __construct()
    {
        $client = Client::getInstance();
        $response = $client->getLanguages();
        $this->languagesAllowed = $response["complete"];
    }

    private function initManager() {
        /*
         * People can change the name of directory plugin so better check a function exists
         */
        if (function_exists("pll_the_languages") || defined( 'POLYLANG_VERSION' )) {
            $this->manager = new Polylang($this->languagesAllowed);
        } else {
            throw new \Exception("Missing required plugin");
        }
    }

    public function getLanguageManager(): LanguageInterface {
        if (!$this->manager) {
            $this->initManager();
        }
        return $this->manager;
    }
}