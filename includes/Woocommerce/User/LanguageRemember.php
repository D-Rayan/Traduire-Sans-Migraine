<?php

namespace TraduireSansMigraine\Woocommerce\User;

class LanguageRemember
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('init', [$this, 'rememberLanguage']);
    }

    public function rememberLanguage()
    {
        global $tsm;
        if (function_exists("is_user_logged_in") && is_user_logged_in() && !is_admin()) {
            $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
            update_user_meta(get_current_user_id(), 'tsm_language', $currentLanguage);
        }
    }

}

$LanguageRemember = new LanguageRemember();
$LanguageRemember->init();