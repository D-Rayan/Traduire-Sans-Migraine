<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Terms;


use Exception;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractApplyTranslation;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends AbstractApplyTranslation
{
    /**
     * @var int $translatedId
     */
    private $translatedId = null;
    /**
     * @var TranslationTerms $translations
     */
    private $translations;


    /**
     * @var $action Action
     */
    public function __construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        $data = $this->getData();
        if ($data === false) {
            return;
        }

        $this->translations = TranslationTerms::findTranslationFor($this->originalObject->term_id);
        if (empty($this->translations->getTranslation($this->codeTo))) {
            $this->createTerm($data);
        } else {
            $this->updateTerm($data);
        }
        $this->translatedId = $this->translations->getTranslation($this->codeTo);
        $this->handleTermMeta();
    }

    private function getData()
    {
        $data = [];
        if (empty($this->dataToTranslate["name"])) {
            return false;
        }
        $data["name"] = $this->dataToTranslate["name"];
        if (isset($this->dataToTranslate["description"])) {
            $data["description"] = $this->dataToTranslate["description"];
        }
        if (isset($this->dataToTranslate["slug"])) {
            $data["slug"] = $this->dataToTranslate["slug"];
        }
        if (!empty($this->originalObject->parent)) {
            $translations = TranslationTerms::findTranslationFor($this->originalObject->parent);
            if (!empty($translations->getTranslation($this->codeTo))) {
                $data["parent"] = $translations->getTranslation($this->codeTo);
            }
        }
        return $data;
    }

    private function createTerm($data)
    {
        $nameTerm = get_term_by("name", $data["name"], $this->originalObject->taxonomy);
        if (!is_wp_error($nameTerm) && !empty($nameTerm)) {
            $data["name"] .= "-" . $this->codeTo;
        }
        $newTerm = wp_insert_term($data["name"], $this->originalObject->taxonomy, $data);
        if (is_wp_error($newTerm)) {
            return;
        }
        LanguageTerm::setLanguage($newTerm["term_id"], $this->codeTo);
        $this->translations
            ->addTranslation($this->codeTo, $newTerm["term_id"])
            ->addTranslation($this->codeFrom, $this->originalObject->term_id)
            ->save();
    }

    private function updateTerm($data)
    {
        $termToUpdate = get_term($this->translations->getTranslation($this->codeTo));
        if (!$termToUpdate) {
            $this->createTerm($data);
            return;
        }
        wp_update_term($termToUpdate->term_id, $termToUpdate->taxonomy, $data);
    }

    private function handleTermMeta()
    {
        if (empty($this->translatedId)) {
            return;
        }
        $termMeta = get_term_meta($this->originalObject->term_id);
        foreach ($termMeta as $key => $values) {
            $value = $values[0];
            update_term_meta($this->translatedId, $key, $value);
        }
    }

    protected function getCodeFrom()
    {
        $language = LanguageTerm::getLanguage($this->originalObject->term_id);
        if (empty($language)) {
            throw new Exception("Language not found");
        }
        return $language["code"];
    }

    protected function getTranslatedId()
    {
        return $this->translatedId;
    }
}