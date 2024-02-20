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
     */
    public function getCurrentLanguage(): string;

    /**
     * @return array[]
     * @throws Exception
     */
    public function getLanguages(): array;

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
     * @param string $postId
     * @param string $codeLanguage
     * @param string $translatedPostId
     * @param string $codeFrom
     * @throws Exception
     */
    public function setTranslationPost(string $postId, string $codeLanguage, string $translatedPostId, string $codeFrom);

    /**
     * @param array $categories
     * @param string $codeLanguage
     * @throws Exception
     */
    public function getTranslationCategories(array $categories, string $codeLanguage): array;

    public function getLanguageManagerName(): string;
}