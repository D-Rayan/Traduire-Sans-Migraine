<?php

namespace TraduireSansMigraine\Wordpress;

class TextDomain
{
    public function loadTextDomain()
    {
        load_plugin_textdomain('traduire-sans-migraine', false, TSM__RELATIVE_PATH . '/languages');
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