<?php

namespace TraduireSansMigraine\Woocommerce\Email\Admin;

class Submit
{
    public function __construct()
    {

    }

    public function init()
    {
        if (!is_admin()) {
            return;
        }
        add_action('woocommerce_loaded', [$this, 'addFilters']);
    }

    public function addFilters()
    {
        $emails = wc()->mailer()->get_emails();
        $self = $this;
        foreach ($emails as $email) {
            add_filter('woocommerce_settings_api_sanitized_fields_' . $email->id, function ($settings) use ($email, $self) {
                $email->init_settings();
                return $self->injectUpdateTime($settings, $email->settings);
            });
        }
    }

    public function injectUpdateTime($settings, $originalSettings)
    {
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        foreach ($languages as $language) {
            $slug = $language["default"] ? null : $language["code"];

            $hasBeenUpdated = $this->fieldIsUpdated($slug, "additional_content", $settings, $originalSettings) ||
                $this->fieldIsUpdated($slug, "subject", $settings, $originalSettings) ||
                $this->fieldIsUpdated($slug, "header", $settings, $originalSettings);
            if (!$hasBeenUpdated) {
                continue;
            }
            $updatedTimeField = empty($slug) ? "updatedTime" : "tsm-language-" . $slug . "-updatedTime";
            $settings[$updatedTimeField] = time();
        }

        return $settings;
    }

    private function fieldIsUpdated($slug, $field, $settings, $originalSettings)
    {
        if (!empty($slug)) {
            $field = "tsm-language-" . $slug . "-" . $field;
        }
        return isset($settings[$field]) && (!isset($originalSettings[$field]) || $settings[$field] !== $originalSettings[$field]);
    }
}

$Submit = new Submit();
$Submit->init();
