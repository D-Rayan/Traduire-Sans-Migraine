<?php

namespace TraduireSansMigraine\Languages;

if (!defined("ABSPATH")) {
    exit;
}

class WPML implements LanguageInterface
{
    public function getLanguages(): array
    {
        // TODO: Implement getLanguages() method.
    }

    public function getLanguageForPost(string $postId): string
    {
        // TODO: Implement getLanguageForPost() method.
    }

    public function getTranslationPost(string $postId, string $codeLanguage)
    {
        // TODO: Implement getTranslationPost() method.
    }

    public function getAllTranslationsPost(string $postId): array
    {
        // TODO: Implement getAllTranslationsPost() method.
    }

    public function saveAllTranslationsPost(array $translationsMap)
    {
        // TODO: Implement saveAllTranslationsPost() method.
    }

    function getTranslationCategories(array $categories, string $codeLanguage): array
    {
        // TODO: Implement getTranslationCategories() method.
    }

    public function getCurrentLanguage(): string
    {
        // TODO: Implement getCurrentLanguage() method.
    }

    public function setTranslationPost(string $postId, string $codeLanguage, string $translatedPostId)
    {
        // TODO: Implement setTranslationPost() method.
    }

    public function getLanguageManagerName(): string
    {
        return "WPML";
    }
}