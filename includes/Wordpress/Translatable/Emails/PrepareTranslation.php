<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Emails;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractPrepareTranslation;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
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
            "subject" => $email->get_option('subject') ?? $email->get_default_subject(),
            "heading" => $email->get_option('heading') ?? $email->get_default_heading(),
            "additional_content" => $email->get_option('additional_content') ?? $email->get_default_additional_content(),
        ];
        do_action("tsm-enable-emails-query-filter");
    }

    protected function getSlugOrigin()
    {
        $language = Language::getDefaultLanguage();

        return $language["code"];
    }
}