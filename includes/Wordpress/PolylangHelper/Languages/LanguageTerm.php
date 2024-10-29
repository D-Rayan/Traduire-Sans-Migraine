<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Languages;

class LanguageTerm extends Language
{
    public static function getTermIdByLanguage($languageId)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT t.term_id FROM {$wpdb->terms} t 
                WHERE t.term_id > %d AND t.slug=CONCAT('pll_', (SELECT t2.slug FROM {$wpdb->terms} t2 WHERE t2.term_id = %d))", $languageId, $languageId));
    }

    public static function getLanguage($objectId)
    {
        return parent::getLanguage($objectId);
    }
}