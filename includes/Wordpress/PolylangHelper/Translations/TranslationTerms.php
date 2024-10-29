<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper\Translations;

class TranslationTerms extends Translation
{
    public function __construct($id = null, $relatedId = null, $translations = [])
    {
        parent::__construct($id, $relatedId, $translations);
    }
}