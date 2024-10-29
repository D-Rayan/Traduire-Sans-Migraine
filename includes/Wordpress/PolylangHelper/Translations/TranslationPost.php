<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Translations;

class TranslationPost extends Translation
{
    public function __construct($id = null, $relatedId = null, $translations = [])
    {
        parent::__construct($id, $relatedId, $translations);
    }


    public function getTranslation($languageSlug)
    {
        $id = parent::getTranslation($languageSlug);
        if (!$id) {
            return null;
        }
        if (get_post_status($id) === "trash") {
            return null;
        }
        return $id;
    }

}