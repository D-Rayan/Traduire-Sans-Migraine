<?php

namespace TraduireSansMigraine\Languages;

use Exception;
use PLL_Admin_Model;
use WP_Error;

if (!defined("ABSPATH")) {
    exit;
}

class PolylangManager
{
    private $languagesAllowed;
    private $glossaries;
    private $languagesPolylang;

    public function __construct()
    {
        if (defined("POLYLANG_DIR")) {
            $this->languagesPolylang = include POLYLANG_DIR . '/settings/languages.php';
        } else {
            $this->languagesPolylang = [];
        }
    }

    public function getLanguageForPost(string $postId): string
    {
        if (!function_exists("pll_get_post_language")) {
            return "";
        }

        return pll_get_post_language($postId, "slug");
    }

    public function getAllTranslationsPost(string $postId): array
    {
        $languages = $this->getLanguagesActives();

        $results = [];
        foreach ($languages as $language) {
            $postIdTranslated = $this->getTranslationPost($postId, $language["code"]);
            $results[$language["code"]] = [
                "name" => $language["name"],
                "default" => (bool)$language["default"],
                "flag" => $language["flag"],
                "code" => $language["code"],
                "postId" => $postIdTranslated,
            ];
        }

        return $results;
    }

    public function getLanguagesActives(): array
    {
        if (!function_exists("pll_languages_list")) {
            return [];
        }

        $languages = pll_languages_list(['fields' => []]);
        $results = [];
        foreach ($languages as $language) {
            $results[$language->slug] = [
                "id" => $language->term_id,
                "default" => (bool)$language->is_default,
                "name" => $language->name,
                "flag" => $language->flag,
                "code" => $language->slug,
                "term_group" => $language->term_group,
            ];
        }

        return $results;
    }

    public function getTranslationPost(string $postId, string $codeLanguage)
    {
        if (!function_exists("pll_get_post")) {
            return null;
        }

        $postId = pll_get_post($postId, $codeLanguage);

        return $postId && get_post_status($postId) !== "trash" ? $postId : null;
    }

    public function getAllTranslationsTerm(string $termId): array
    {
        $languages = $this->getLanguagesActives();

        $results = [];
        foreach ($languages as $language) {
            $results[$language["code"]] = [
                "name" => $language["name"],
                "flag" => $language["flag"],
                "code" => $language["code"],
                "termId" => pll_get_term($termId, $language["code"])
            ];
        }

        return $results;
    }

    public function saveAllTranslationsPost(array $translationsMap)
    {
        if (!function_exists("pll_save_post_translations")) {
            return;
        }

        $languages = $this->getLanguagesActives();
        $cleanMap = [];
        foreach ($translationsMap as $codeLanguage => $postId) {
            if (!isset($languages[$codeLanguage]) || empty($postId)) {
                continue;
            }
            $cleanMap[$codeLanguage] = $postId;
            pll_set_post_language($postId, $codeLanguage);
        }
        pll_save_post_translations($cleanMap);
    }

    public function saveAllTranslationsTerms(array $translationsMap)
    {
        if (!function_exists("pll_save_term_translations")) {
            return;
        }

        $languages = $this->getLanguagesActives();
        $cleanMap = [];
        foreach ($translationsMap as $codeLanguage => $term) {
            $termId = is_array($term) ? $term["termId"] : $term;
            if (!isset($languages[$codeLanguage]) || empty($termId)) {
                continue;
            }
            $cleanMap[$codeLanguage] = $termId;
            pll_set_term_language($termId, $codeLanguage);
        }
        pll_save_term_translations($cleanMap);
    }

    function getTranslationCategories(array $categories, string $codeLanguage): array
    {
        if (!function_exists("pll_get_term")) {
            return [];
        }

        $results = [];
        foreach ($categories as $index => $category) {
            $categoryTranslated = pll_get_term($category, $codeLanguage);
            if ($categoryTranslated) {
                $results[$index] = $categoryTranslated;
            }
        }

        return $results;
    }

    public function addLanguage(string $locale): bool
    {
        if (!class_exists("PLL_Admin_Model")) {
            return false;
        }
        $options = get_option('polylang');
        $model = new PLL_Admin_Model($options);
        $model->set_languages_ready();
        $is_first_language = !function_exists("pll_languages_list");
        $all_languages = include POLYLANG_DIR . '/settings/languages.php';
        $saved_languages = array();

        require_once ABSPATH . 'wp-admin/includes/translation-install.php';
        if (!isset($all_languages[$locale])) {
            return false;
        }
        $saved_languages = $all_languages[$locale];

        $saved_languages['slug'] = $saved_languages['code'];
        $saved_languages['rtl'] = (int)('rtl' === $saved_languages['dir']);
        $saved_languages['term_group'] = 0; // Default term_group.

        $language_added = $model->add_language($saved_languages);

        if ($language_added instanceof WP_Error && array_key_exists('pll_non_unique_slug', $language_added->errors)) {
            $saved_languages['slug'] = strtolower(str_replace('_', '-', $saved_languages['locale']));
            $language_added = $model->add_language($saved_languages);
        }

        if ($language_added instanceof WP_Error) {
            return false;
        }

        if ('en_US' !== $locale && current_user_can('install_languages')) {
            wp_download_language_pack($locale);
        }
        ob_start();
        try {
            if ($is_first_language) {
                $model->set_language_in_mass();
            }
        } catch (Exception $e) {
        }
        ob_get_clean();
        return true;
    }

    public function updateLanguage($slug, $localeDeepL)
    {
        if (!class_exists("PLL_Admin_Model")) {
            return false;
        }
        $options = get_option('polylang');
        $model = new PLL_Admin_Model($options);
        $model->set_languages_ready();

        $languagePolylang = $this->getLanguagePolylangByIncompleteLocale($localeDeepL);
        if (!$languagePolylang) {
            return false;
        }
        $languages = $this->getLanguagesActives();
        if (!isset($languages[$slug])) {
            return false;
        }
        $langId = $languages[$slug]["id"];
        $language_added = $model->update_language(array_merge([
            "lang_id" => $langId,
            "slug" => $languagePolylang["code"],
            "term_group" => $languages[$slug]["term_group"],
        ], $languagePolylang));

        if ($language_added instanceof WP_Error) {
            return $language_added->get_error_message();
        }

        return true;
    }

    public function getLanguagePolylangByIncompleteLocale($incompleteLocale)
    {
        $bestMatch = null;
        $incompleteLocaleLower = strtolower($incompleteLocale);
        foreach ($this->languagesPolylang as $language) {
            if (!isset($language["code"]) || !isset($language["locale"])) {
                continue;
            }
            $slug = strtolower($language["code"]);
            $locale = strtolower($language["locale"]);
            if ($locale === $incompleteLocaleLower
                || $locale === str_replace("-", "_", $incompleteLocaleLower)
                || $locale === str_replace("_", "-", $incompleteLocaleLower)) {
                return $language;
            }
            if ($locale === $incompleteLocaleLower . "_" . $incompleteLocaleLower) {
                return $language;
            }
            if ($locale === $incompleteLocaleLower . "-" . $incompleteLocaleLower) {
                return $language;
            }
            if ($slug === $incompleteLocaleLower) {
                $bestMatch = $language;
            }
        }
        return $bestMatch;
    }

    public function getLanguages()
    {
        global $tsm;

        try {
            $enabledLanguages = $this->getLanguagesActives();
        } catch (Exception $e) {
            $enabledLanguages = [];
        }

        try {
            $account = $tsm->getClient()->getAccount();
            if (isset($account["slugs"]["allowed"])) {
                $languagesEnabledOnTSM = $account["slugs"]["allowed"];
            } else {
                $languagesEnabledOnTSM = [];
            }
        } catch (Exception $e) {
            $languagesEnabledOnTSM = [];
            $account = null;
        }

        $languages = [];
        foreach ($this->getLanguagesAllowed() as $language) {
            $polylangLanguage = $this->getLanguagePolylangByIncompleteLocale($language["language"]);
            if (!$polylangLanguage) {
                continue;
            }
            $slug = strtolower(substr($language["language"], 0, 2));
            $glossaries = [];
            foreach ($this->glossaries as $glossary) {
                if ($glossary["target_lang"] === $slug) {
                    $glossaries[] = $glossary["source_lang"];
                }
            }
            $options = [];
            if (isset($account["slugs"]["options"][$slug])) {
                $options = $account["slugs"]["options"][$slug];
                if (isset($options["country"])) {
                    $tmp = explode("-", $options["country"]);
                    $options["country"] = strtolower($tmp[0]) . "_" . strtoupper($tmp[1]);
                }
            }
            $languages[] = [
                "slug" => $slug,
                "locale" => $polylangLanguage["locale"],
                "enabled" => isset($enabledLanguages[$slug]),
                "default" => isset($enabledLanguages[$slug]) ? $enabledLanguages[$slug]["default"] : false,
                "name" => $language["name"],
                "simple_name" => explode(" ", $language["name"])[0],
                "supports_formality" => $language["supports_formality"],
                "flag" => $polylangLanguage["flag"],
                "glossaries" => $glossaries,
                "tsmEnabled" => in_array($slug, $languagesEnabledOnTSM),
                "options" => $options
            ];
        }
        usort($languages, function ($a, $b) {
            return strcmp($a["name"], $b["name"]);
        });
        return $languages;
    }

    private function getLanguagesAllowed()
    {
        if (!isset($this->languagesAllowed)) {
            global $tsm;
            $response = $tsm->getClient()->getLanguages();
            $this->languagesAllowed = $response["complete"];
            $this->glossaries = $response["glossaries"];
        }

        return $this->languagesAllowed;
    }
}