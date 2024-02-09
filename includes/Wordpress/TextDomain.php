<?php

namespace TraduireSansMigraine\Wordpress;

class TextDomain
{
    public function loadTextDomain()
    {
        load_plugin_textdomain(TSM__TEXT_DOMAIN, false, TSM__ABSOLUTE_PATH . '/languages');
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