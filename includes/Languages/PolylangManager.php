<?php

namespace TraduireSansMigraine\Languages;

use Exception;

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

    public function getLanguageSlugForPost(string $postId): string
    {
        if (!function_exists("pll_get_post_language")) {
            return "";
        }

        return pll_get_post_language($postId, "slug");
    }

    public function getAllTranslationsPost($postId)
    {
        $languages = $this->getLanguagesActives();

        $results = [];
        foreach ($languages as $language) {
            $postIdTranslated = $this->getTranslationPost($postId, $language["code"]);
            $results[$language["code"]] = array_merge($language, [
                "postId" => $postIdTranslated,
            ]);
        }

        return $results;
    }

    public function getLanguagesActives()
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
                "no_translation" => $language->no_translation
            ];
        }

        return $results;
    }

    public function getTranslationPost($postId, $codeLanguage)
    {
        if (!function_exists("pll_get_post")) {
            return null;
        }

        $postId = pll_get_post($postId, $codeLanguage);

        return $postId && get_post_status($postId) !== "trash" ? $postId : null;
    }

    public function getHomeUrl($slug)
    {
        if (!function_exists("pll_home_url")) {
            return get_home_url();
        }
        return pll_home_url($slug);
    }

    public function getCurrentLanguageSlug()
    {
        if (!function_exists("pll_current_language")) {
            return get_locale();
        }
        return pll_current_language("slug");
    }

    public function getAllTranslationsTerm($termId)
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

    public function saveAllTranslationsPost($translationsMap)
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

    public function saveAllTranslationsTerms($translationsMap)
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

    function getTranslationCategories($categories, $codeLanguage)
    {
        if (!function_exists("pll_get_term") || empty($codeLanguage)) {
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

    public function addLanguage($locale)
    {
        $all_languages = include POLYLANG_DIR . '/settings/languages.php';
        if (!isset($all_languages[$locale])) {
            return false;
        }
        $is_first_language = !function_exists("pll_languages_list");
        if ($is_first_language) {
            $navMenuLocations = get_theme_mod('nav_menu_locations');
            if (isset($navMenuLocations['primary'])) {
                $primaryNavMenu = $navMenuLocations['primary'];
            }
        }
        $data = $all_languages[$locale];
        $response = $this->makeRequestPolylang(admin_url("admin.php?page=mlang&noheader=true"), "POST", [
            "pll_action" => "add",
            "locale" => $data["locale"],
            "slug" => $data["code"],
            "name" => $data["name"],
            "flag" => $data["flag"],
            "rtl" => (int)('rtl' === $data['dir']),
            "term_group" => 0,
            "_wpnonce_add-lang" => wp_create_nonce("add-lang"),
            "_wp_http_referer" => admin_url("admin.php?page=mlang"),
        ]);
        $isSuccess = strpos($response, 'setting-error-pll') === false || strpos($response, 'setting-error-pll_languages_created') !== false;
        if ($isSuccess && $is_first_language) {
            $this->makeRequestPolylang(admin_url("admin.php?page=mlang&pll_action=content-default-lang&noheader=true&_wpnonce=" . wp_create_nonce("content-default-lang")));
            if (isset($primaryNavMenu)) {
                $options = get_option('polylang');
                if (!isset($options['nav_menus'])) {
                    $options['nav_menus'] = [];
                }
                $themeName = get_option('stylesheet');
                $themeMod = get_theme_mod('nav_menu_locations');
                $options['nav_menus'][$themeName] = [];
                foreach ($themeMod as $location => $navId) {
                    $options['nav_menus'][$themeName][$location] = [
                        $data["code"] => $navId
                    ];
                }
                $options["default_lang"] = $data["code"];
                update_option('polylang', $options);
            }
        }
        return $isSuccess;
    }

    private function makeRequestPolylang($url, $method = "GET", $data = null)
    {
        $curl = curl_init();
        curl_setopt_array($curl, [
            CURLOPT_URL => $url,
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_POST => $method === "POST",
            CURLOPT_POSTFIELDS => $method === "POST" ? http_build_query($data) : null,
        ]);
        $cookies = [];
        foreach ($_COOKIE as $key => $value) {
            $cookies[] = $key . "=" . $value;
        }
        curl_setopt($curl, CURLOPT_COOKIE, implode("; ", $cookies));
        curl_setopt($curl, CURLOPT_FOLLOWLOCATION, true);
        $response = curl_exec($curl);
        curl_close($curl);

        return $response;
    }

    public function updateLanguage($slug, $localeDeepL)
    {
        $languagePolylang = $this->getLanguagePolylangByIncompleteLocale($localeDeepL);
        if (!$languagePolylang) {
            return false;
        }

        $languages = $this->getLanguagesActives();
        if (!isset($languages[$slug])) {
            return false;
        }
        $langId = $languages[$slug]["id"];
        $response = $this->makeRequestPolylang(admin_url("admin.php?page=mlang&noheader=true"), "POST", [
            "pll_action" => "update",
            "lang_id" => $langId,
            "locale" => $languagePolylang["locale"],
            "slug" => $languagePolylang["code"],
            "name" => $languagePolylang["name"],
            "flag" => $languagePolylang["flag"],
            "rtl" => (int)('rtl' === $languagePolylang['dir']),
            "term_group" => $languages[$slug]["term_group"],
            "_wpnonce_add-lang" => wp_create_nonce("add-lang"),
            "_wp_http_referer" => admin_url("admin.php?page=mlang"),
        ]);
        $isSuccess = strpos($response, 'setting-error-pll') === false || strpos($response, 'setting-error-pll_languages_updated') !== false;
        return $isSuccess;
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