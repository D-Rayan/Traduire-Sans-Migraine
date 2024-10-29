<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Languages;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

abstract class Language
{
    public static function setLanguage($objectId, $language)
    {
        global $wpdb;

        $result = self::getTermAndTaxonomyIdByLanguage($language);
        if (!$result) {
            return;
        }
        $termId = $result["term_id"];
        $termTaxonomyId = $result["term_taxonomy_id"];

        $oldLanguage = self::getLanguage($objectId);
        if ($oldLanguage) {
            $result = self::getTermAndTaxonomyIdByLanguage($oldLanguage);
            if (!$result || $result["term_id"] == $termId) {
                return;
            }
            self::handleDeleteLanguage($objectId, $oldLanguage);
        }

        $wpdb->insert($wpdb->term_relationships, [
            "object_id" => $objectId,
            "term_taxonomy_id" => $termTaxonomyId
        ]);
    }

    private static function getTermAndTaxonomyIdByLanguage($language)
    {
        global $tsm;

        if (is_array($language)) {
            $termId = $language["id"];
        } else if (is_string($language) || is_int($language)) {
            $languages = $tsm->getPolylangManager()->getLanguagesActives();
            foreach ($languages as $slug => $lang) {
                if ($slug == $language || $lang["id"] == $language) {
                    $termId = $lang["id"];
                    break;
                }
            }
        }
        if (!isset($termId) || empty($termId)) {
            return null;
        }

        if (!self::isPost()) {
            $termId = LanguageTerm::getTermIdByLanguage($termId);
            if (!$termId) {
                return null;
            }
        }

        $termTaxonomyId = self::getTermTaxonomyId($termId);
        if (!$termTaxonomyId) {
            return null;
        }

        return [
            "term_id" => $termId,
            "term_taxonomy_id" => $termTaxonomyId
        ];
    }

    private static function isPost()
    {
        return (static::class === LanguagePost::class);
    }

    public static function getTermTaxonomyId($termId)
    {
        global $wpdb;
        return $wpdb->get_var($wpdb->prepare("SELECT term_taxonomy_id FROM {$wpdb->term_taxonomy} WHERE term_id = %d", $termId));
    }

    protected static function getLanguage($objectId)
    {
        global $wpdb, $tsm;
        $taxonomy = self::isPost() ? "language" : "term_language";
        $result = $wpdb->get_row($wpdb->prepare("SELECT t.slug, tr.term_taxonomy_id FROM {$wpdb->term_relationships} tr
            LEFT JOIN {$wpdb->term_taxonomy} tt ON tr.term_taxonomy_id = tt.term_taxonomy_id
            LEFT JOIN {$wpdb->terms} t ON t.term_id = tt.term_id
            WHERE tr.object_id = %d AND tt.taxonomy=%s", $objectId, $taxonomy));

        if (!$result) {
            return null;
        }

        $slug = $result->slug;
        $taxonomyId = $result->term_taxonomy_id;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (self::isPost() && !isset($languages[$slug])) {
            return null;
        } else if (self::isPost()) {
            return $languages[$slug];
        }

        if (strpos($slug, "pll_") !== 0) {
            // we have a language but not a term_language delete it
            $wpdb->delete($wpdb->term_relationships, [
                "object_id" => $objectId,
                "term_taxonomy_id" => $taxonomyId
            ]);
            return null;
        }
        $slug = str_replace("pll_", "", $slug);
        if (!isset($languages[$slug])) {
            return null;
        }


        return $languages[$slug];
    }

    private static function handleDeleteLanguage($objectId, $language)
    {
        global $wpdb;
        $result = self::getTermAndTaxonomyIdByLanguage($language);
        $wpdb->delete($wpdb->term_relationships, [
            "object_id" => $objectId,
            "term_taxonomy_id" => $result["term_taxonomy_id"]
        ]);
        if (self::isPost()) {
            $translation = TranslationPost::findTranslationFor($objectId);
        } else {
            $translation = TranslationTerms::findTranslationFor($objectId);
        }
        $translation->removeTranslation($language["code"])->save();
    }

    public static function getDefaultLanguage()
    {
        global $tsm;
        foreach ($tsm->getPolylangManager()->getLanguagesActives() as $languageActive) {
            if ($languageActive["default"]) {
                $language = $languageActive;
                break;
            }
        }
        if (!isset($language)) {
            return [
                "code" => null,
                "id" => -1
            ];
        }
        return $language;
    }
}