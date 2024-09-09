<?php

namespace TraduireSansMigraine\Wordpress;

class TextDomain
{
    private $determineLocale;
    public function loadTextDomain()
    {
        $this->determineLocale = determine_locale();
        load_plugin_textdomain("traduire-sans-migraine", false, TSM__PLUGIN_NAME . '/languages');
        add_filter( 'load_textdomain_mofile', [$this, "loadMOFile"], 10, 2 );
    }

    function loadMOFile( $mofile, $domain ) {
        if ( "traduire-sans-migraine" === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
            $locale = apply_filters( 'plugin_locale', $this->determineLocale, $domain );
            $mofile = TSM__ABSOLUTE_PATH . '/languages/' . $domain . '-' . $locale . '.mo';
        }
        return $mofile;
    }

    public static function __($text, ...$args) {
        if (empty($args)) {
            return __($text, "traduire-sans-migraine");
        }
        return sprintf(__($text, "traduire-sans-migraine"), ...$args);
    }

    public static function _n($single, $plural, $number, ...$args) {
        if (empty($args)) {
            return $number > 1 ? self::__($plural) : self::__($single);
        }
        return $number > 1 ? self::__($plural, ...$args) : self::__($single, ...$args);
    }

    // fake use for CLI
    public static function _f($text) {
        return $text;
    }
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function init() {
        $instance = self::getInstance();
        $instance->loadTextDomain();
    }
}