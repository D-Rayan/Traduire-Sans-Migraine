<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Emails;


use Exception;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\Language;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractApplyTranslation;
use WC_Email;

if (!defined("ABSPATH")) {
    exit;
}

class ApplyTranslation extends AbstractApplyTranslation
{
    public function ___construct($action, $translationData)
    {
        parent::__construct($action, $translationData);
    }

    public function processTranslation()
    {
        $subject = $this->dataToTranslate["subject"];
        $heading = $this->dataToTranslate["heading"];
        $additional_content = $this->dataToTranslate["additional_content"];
        /**
         * @var $email WC_Email
         */
        $email = $this->originalObject;
        $email->init_settings();
        $field = "tsm-language-" . $this->codeTo . "-additional_content";
        $email->settings[$field] = $additional_content;
        $field = "tsm-language-" . $this->codeTo . "-subject";
        $email->settings[$field] = $subject;
        $field = "tsm-language-" . $this->codeTo . "-header";
        $email->settings[$field] = $heading;
        $option_key = $email->get_option_key();
        do_action('woocommerce_update_option', array('id' => $option_key));
        return update_option($option_key, apply_filters('woocommerce_settings_api_sanitized_fields_' . $email->id, $email->settings), 'yes');
    }

    protected function getCodeFrom()
    {
        $language = Language::getDefaultLanguage();
        if (empty($language)) {
            throw new Exception("Language not found");
        }
        return $language["code"];
    }

    protected function getTranslatedId()
    {
        return $this->originalObject->id;
    }
}