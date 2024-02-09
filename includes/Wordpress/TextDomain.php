<?php

namespace TraduireSansMigraine\Wordpress;

class TextDomain
{
    public function loadTextDomain()
    {
        load_plugin_textdomain(TSM__TEXT_DOMAIN, false, TSM__ABSOLUTE_PATH . '/languages');
        add_filter( 'load_textdomain_mofile', [$this, "loadMOFile"], 10, 2 );
    }

    function loadMOFile( $mofile, $domain ) {
        if ( TSM__TEXT_DOMAIN === $domain && false !== strpos( $mofile, WP_LANG_DIR . '/plugins/' ) ) {
            $locale = apply_filters( 'plugin_locale', determine_locale(), $domain );
            $mofile = WP_PLUGIN_DIR . '/' . TSM__PLUGIN_NAME . '/languages/' . $domain . '-' . $locale . '.mo';
        }
        return $mofile;
    }


    public static function __($text, ...$args) {
        if (empty($args)) {
            return __($text, TSM__TEXT_DOMAIN);
        }
        return sprintf(__($text, TSM__TEXT_DOMAIN), ...$args);
    }

    public static function _n($single, $plural, $number, ...$args) {
        if (empty($args)) {
            return _n($single, $plural, $number, TSM__TEXT_DOMAIN);
        }
        return sprintf(_n($single, $plural, $number, TSM__TEXT_DOMAIN), ...$args);
    }
}