<?php

namespace TraduireSansMigraine\Woocommerce\Email;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use WC_Order;
use WC_Product;
use WP_User;

class Query
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('woocommerce_email_header', [$this, 'beforeSendingMail']);
        add_action('woocommerce_loaded', [$this, 'addFilters']);
        add_action('tsm-disable-emails-query-filter', [$this, 'removeFilters']);
        add_action('tsm-enable-emails-query-filter', [$this, 'addFilters']);
    }

    public function addFilters()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            add_filter('woocommerce_email_additional_content_' . $email->id, [$this, "returnAdditionalContent"], 10, 3);
            add_filter('woocommerce_email_subject_' . $email->id, [$this, "returnSubject"], 10, 3);
            add_filter('woocommerce_email_heading_' . $email->id, [$this, "returnHeading"], 10, 3);
        }
    }

    public function removeFilters()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            remove_filter('woocommerce_email_additional_content_' . $email->id, [$this, "returnAdditionalContent"], 10);
            remove_filter('woocommerce_email_subject_' . $email->id, [$this, "returnSubject"], 10);
            remove_filter('woocommerce_email_heading_' . $email->id, [$this, "returnHeading"], 10);
        }
    }

    public function returnAdditionalContent($value, $object, $instance)
    {
        $slug = $this->getSlug($object);
        $field = "tsm-language-" . $slug . "-additional_content";
        $result = $instance->format_string($instance->settings[$field]);

        return !empty($result) ? $result : $value;
    }

    /**
     * @param $object WC_Order|WC_Product|WP_User
     * @return string
     */
    private function getSlug($object)
    {
        if ($object instanceof WP_User) {
            $userId = $object->ID;
        }
        if ($object instanceof WC_Order && count($object->get_items()) > 0) {
            $productId = $object->get_items()[0]->get_id();
        }
        if ($object instanceof WC_Product) {
            $productId = $object->get_id();
        }
        if (isset($productId)) {
            $language = LanguagePost::getLanguage($productId);
            if (!empty($language)) {
                return $language["code"];
            }
        }
        if (isset($userId)) {
            $slug = get_user_meta($userId, "tsm-language", true);
            if (!empty($slug)) {
                return $slug;
            }
        }
        return null;
    }

    public function returnSubject($value, $object, $instance)
    {
        $slug = $this->getSlug($object);
        $field = "tsm-language-" . $slug . "-subject";
        $result = $instance->format_string($instance->settings[$field]);

        return !empty($result) ? $result : $value;
    }

    public function returnHeading($value, $object, $instance)
    {
        $slug = $this->getSlug($object);
        $self = $this;
        add_filter('plugin_locale', function ($oldLocale, $domain) use ($self, $slug) {
            return $self->getLocale($oldLocale, $domain, $slug);
        }, 100);
        WC()->load_plugin_textdomain();

        $field = "tsm-language-" . $slug . "-header";
        $result = $instance->format_string($instance->settings[$field]);

        return !empty($result) ? $result : $value;
    }

    public function getLocale($locale, $from, $slug)
    {
        if ($from !== "woocommerce") {
            return $locale;
        }
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $id = $languages[$slug]["id"];
        $languages = $tsm->getPolylangManager()->getLanguages();
        foreach ($languages as $language) {
            if ($language["id"] == $id) {
                return $language["locale"];
            }
        }
        return $locale;
    }
}

$Query = new Query();
$Query->init();