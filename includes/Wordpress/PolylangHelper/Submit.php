<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Submit
{

    public function __construct()
    {

    }

    public function init()
    {
        add_action("tsm_save_entity", [$this, "handleSave"], 10, 4);
    }

    public function handleSave($objectId, $languageId, $translationsReceived, $isProduct)
    {
        global $tsm;
        $TranslationClass = $isProduct ? TranslationPost::class : TranslationTerms::class;
        $LanguageClass = $isProduct ? LanguagePost::class : LanguageTerm::class;

        $activesLanguages = $tsm->getPolylangManager()->getLanguagesActives();
        $translations = $TranslationClass::findTranslationFor($objectId);
        $translationsToUpdate = [];
        foreach ($activesLanguages as $language) {
            if ($language["id"] == $languageId) {
                $translations->addTranslation($language["code"], $objectId);
                continue;
            }
            $categoryIdLinked = $translationsReceived[$language["id"]] ?? 0;
            if (empty($categoryIdLinked)) {
                $translations->removeTranslation($language["code"]);
                continue;
            }
            if ($categoryIdLinked == $objectId) {
                wp_die(__LINE__);
            }
            $languageLinked = $LanguageClass::getLanguage($categoryIdLinked);
            if (!$languageLinked || $languageLinked["id"] != $language["id"]) {
                return;
            }
            $translations->addTranslation($language["code"], $categoryIdLinked);
            $translationsLinked = $TranslationClass::findTranslationFor($categoryIdLinked);
            if (!$translationsLinked->getId() || $translations->getId() == $translationsLinked->getId()) {
                continue;
            }
            if (!$translations->canMerge($translationsLinked)) {
                return;
            }
            $translations->merge($translationsLinked);
            $translationsToUpdate[] = $translationsLinked;
        }
        foreach ($translationsToUpdate as $translationToUpdate) {
            if ($translationToUpdate->getId() == $translations->getId()) {
                continue;
            }
            $translationToUpdate->save();
        }
        $translations->save();
        $LanguageClass::setLanguage($objectId, $languageId);
    }
}

$Submit = new Submit();
$Submit->init();