<?php

namespace TraduireSansMigraine\Languages;

if (!defined("ABSPATH")) {
    exit;
}
class LanguageManager
{
    /**
     * @var LanguageInterface $manager
     */
    private $manager;

    /**
     * @throws \Exception
     */
    public function __construct()
    {

    }

    private function initManager() {
        /*
         * People can change the name of directory plugin so better check a function exists
         */
        if (function_exists("pll_the_languages") || defined( 'POLYLANG_VERSION' )) {
            $this->manager = new Polylang();
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