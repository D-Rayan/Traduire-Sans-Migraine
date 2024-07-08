<?php

namespace TraduireSansMigraine\Languages;

use TraduireSansMigraine\Locker;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}
class Polylang implements LanguageInterface
{
    public function getLanguages(): array
    {
        if (!function_exists("pll_languages_list")) {
            throw new \Exception(TextDomain::__("%s not existing.", "pll_languages_list"));
        }

        $languages = pll_languages_list(['fields' => []]);
        $results = [];
        foreach ($languages as $language) {
            $results[$language->slug] = [
                "id" => $language->term_id,
                "default" => $language->is_default,
                "name" => $language->name,
                "flag" => $language->flag,
                "code" => $language->slug,
            ];
        }

        return $results;
    }

    public function getLanguageForPost(string $postId): string
    {
        if (!function_exists("pll_get_post_language")) {
            throw new \Exception(TextDomain::__("Polylang seems to not be configured correctly.", "pll_get_post_language"));
        }

        return pll_get_post_language($postId, "slug");
    }

    public function getTranslationPost(string $postId, string $codeLanguage)
    {
        if (!function_exists("pll_get_post")) {
            throw new \Exception(TextDomain::__("%s not existing.", "pll_get_post"));
        }

        $postId = pll_get_post($postId, $codeLanguage);

        return $postId && get_post_status($postId) !== "trash" ? $postId : null;
    }

    public function getAllTranslationsPost(string $postId): array
    {
        $languages = $this->getLanguages();

        $results = [];
        foreach ($languages as $language) {
            $results[$language["code"]] = [
                "name" => $language["name"],
                "flag" => $language["flag"],
                "code" => $language["code"],
                "postId" => $this->getTranslationPost($postId, $language["code"])
            ];
        }

        return $results;
    }

    public function getAllTranslationsTerm(string $termId): array
    {
        $languages = $this->getLanguages();

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
            throw new \Exception(TextDomain::__("%s not existing.", "pll_save_post_translations"));
        }

        $languages = $this->getLanguages();
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
            throw new \Exception(TextDomain::__("%s not existing.", "pll_save_term_translations"));
        }

        $languages = $this->getLanguages();
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
            throw new \Exception(TextDomain::__("%s not existing.", "pll_get_term"));
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

    public function getCurrentLanguage(): string
    {
        if (!function_exists("pll_current_language")) {
            throw new \Exception(TextDomain::__("%s not existing.", "pll_current_language"));
        }

        return pll_current_language("slug");
    }

    public function getDefaultLanguage()
    {
        $languages = $this->getLanguages();

        foreach ($languages as $language) {
            if ($language["default"]) {
                return $language;
            }
        }

        return false;
    }

    public function getLanguageManagerName(): string
    {
        return "Polylang";
    }
}