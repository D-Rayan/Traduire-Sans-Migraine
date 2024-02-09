<?php

namespace TraduireSansMigraine\Wordpress;

class TextDomain
{
    public function loadTextDomain()
    {
        load_plugin_textdomain("traduire-sans-migraine", false, TSM__ABSOLUTE_PATH . '/languages');
        add_filter( 'load_textdomain_mofile', [$this, "loadMOFile"], 10, 2 );
    }

    function loadMOFile( $mofile, $domain ) {
        if ( "traduire-sans-migraine" === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
            $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
            $mofile = WP_PLUGIN_DIR . '/' . TSM__PLUGIN_NAME . '/languages/' . $domain . '-' . $locale . '.mo';
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