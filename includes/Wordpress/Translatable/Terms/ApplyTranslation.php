<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;


use TraduireSansMigraine\Wordpress\AbstractClass\AbstractApplyTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends AbstractApplyTranslation
{
    private $translatedId = null;

    public function __construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        $translations = TranslationTerms::findTranslationFor($this->originalObject->term_id);
        if (empty($translations->getTranslation($this->codeTo))) {
            $this->createTerm($this->originalObject->term_id, $translations);
        } else {
            $this->updateTerm($this->originalObject->term_id, $translations);
        }
        $this->handleParent($this->originalObject->parent);
    }

    private function createTerm($originalTermId, $translations)
    {
        $originalTerm = ($originalTermId === $this->originalObject->term_id) ? $this->originalObject : get_term($originalTermId);
        $prefix = ($originalTermId === $this->originalObject->term_id) ? "" : "term_" . $originalTermId . "_";
        $data = $this->getData($prefix);
        if ($data === false) {
            return;
        }
        $newTermId = wp_insert_term($data["name"], $originalTerm->taxonomy, $data);
        if (is_wp_error($newTermId)) {
            return;
        }
        $this->translatedId = $newTermId["term_id"];
        LanguageTerm::setLanguage($newTermId["term_id"], $this->codeTo);
        $translations->addTranslation($this->codeTo, $newTermId["term_id"])->save();
    }

    private function getData($prefix = "")
    {
        $data = [];
        if (empty($this->dataToTranslate[$prefix . "name"])) {
            return false;
        }
        $data["name"] = $this->dataToTranslate[$prefix . "name"];
        if (isset($this->dataToTranslate[$prefix . "description"])) {
            $data["description"] = $this->dataToTranslate[$prefix . "description"];
        }
        if (isset($this->dataToTranslate[$prefix . "slug"])) {
            $data["slug"] = $this->dataToTranslate[$prefix . "slug"];
        }
        return $data;
    }

    private function updateTerm($originalTermId, $translations)
    {
        $termToUpdate = get_term($translations->getTranslation($this->codeTo));
        if (!$termToUpdate) {
            $this->createTerm($originalTermId, $translations);
            return;
        }
        $prefix = ($originalTermId === $this->originalObject->term_id) ? "" : "term_" . $originalTermId . "_";
        $data = $this->getData($prefix);
        if ($data === false) {
            return;
        }
        $this->translatedId = $termToUpdate->term_id;
        wp_update_term($termToUpdate->term_id, $termToUpdate->taxonomy, $data);
    }

    private function handleParent($parentId)
    {
        if (!$parentId) {
            return;
        }
        $parent = get_term($parentId);
        $translations = TranslationTerms::findTranslationFor($parentId);
        if (empty($translations->getTranslation($this->codeTo))) {
            $this->createTerm($parentId, $translations);
        } else {
            $this->updateTerm($parentId, $translations);
        }
        $this->handleParent($parent->parent);
    }

    protected function getTranslatedId()
    {
        return $this->translatedId;
    }
}