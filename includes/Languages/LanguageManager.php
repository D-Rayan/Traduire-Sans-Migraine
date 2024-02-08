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
        /*
         * People can change the name of directory plugin so better check a function exists
         */
        if (function_exists("pll_the_languages")) {
            $this->manager = new Polylang();
        } else if (function_exists("wpml_current_language")) {
            $this->manager = new WPML();
        } else if (function_exists("mlp_get_interlinked_permalinks")) {
            $this->manager = new MultilingualPress();
        } else {
            throw new \Exception("Missing required plugins.");
        }
    }

    public function getLanguageManager(): LanguageInterface {
        return $this->manager;
    }
}