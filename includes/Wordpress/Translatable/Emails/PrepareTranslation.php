<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Emails;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractPrepareTranslation;
use WC_Email;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends AbstractPrepareTranslation
{
    public function prepareDataToTranslate()
    {
        /**
         * @var $email WC_Email
         */
        $email = $this->object;
        do_action("tsm-disable-emails-query-filter");
        $email->init_settings();
        $this->dataToTranslate = [
            "subject" => empty($email->get_option('subject')) ? $email->get_default_subject() : $email->get_option('subject'),
            "heading" => empty($email->get_option('heading')) ? $email->get_default_heading() : $email->get_option('heading'),
            "additional_content" => empty($email->get_option('additional_content')) ? $email->get_default_additional_content() : $email->get_option('additional_content'),
        ];
        do_action("tsm-enable-emails-query-filter");
    }

    protected function getSlugOrigin()
    {
        $language = Language::getDefaultLanguage();

        return $language["code"];
    }
}