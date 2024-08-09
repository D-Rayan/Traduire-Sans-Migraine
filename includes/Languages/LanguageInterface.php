<?php

namespace TraduireSansMigraine\Languages;

use Exception;

if (!defined("ABSPATH")) {
    exit;
}
interface LanguageInterface
{
    /**
     * @return string
     * @throws Exception
     * @deprecated
     */
    public function getCurrentLanguage(): string;

    /**
     * @return array[]
     * @throws Exception
     */
    public function getLanguagesActives(): array;

    /**
     * @param string $postId
     * @return string
     * @throws Exception
     */
    public function getLanguageForPost(string $postId): string;

    /**
     * @param string $postId
     * @param string $codeLanguage
     * @return string|null
     * @throws Exception
     */
    public function getTranslationPost(string $postId, string $codeLanguage);

    /**
     * @param string $postId
     * @return mixed
     * @throws Exception
     */
    public function getAllTranslationsPost(string $postId): array;


    /**
     * @param array $translationsMap
     * @throws Exception
     */
    public function saveAllTranslationsPost(array $translationsMap);

    /**
     * @param string $termId
     * @return mixed
     * @throws Exception
     */
    public function getAllTranslationsTerm(string $termId): array;

    /**
     * @param array $translationsMap
     * @throws Exception
     */
    public function saveAllTranslationsTerms(array $translationsMap);

    /**
     * @param array $categories
     * @param string $codeLanguage
     * @throws Exception
     */
    public function getTranslationCategories(array $categories, string $codeLanguage): array;

    public function getLanguageManagerName(): string;

    public function getDefaultLanguage();

    public function addLanguage(string $language): bool;

    public function setLanguageForPost(string $postId, string $codeLanguage);

    public function getLanguages();
}