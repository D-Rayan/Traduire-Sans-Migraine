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
            return _n($single, $plural, $number, "traduire-sans-migraine");
        }
        return sprintf(_n($single, $plural, $number, "traduire-sans-migraine"), ...$args);
    }
}