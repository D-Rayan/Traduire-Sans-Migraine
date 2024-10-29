<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class GetVariables
{
    public function __construct()
    {
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function loadHooks()
    {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }

    public function loadHooksAdmin()
    {
        add_action("wp_ajax_traduire-sans-migraine_get_variables", [$this, "getVariables"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getVariables()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["objectId"]) || !isset($_GET["objectType"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $objectType = $_GET["objectType"];
        $objectId = $_GET["objectId"];
        if (!in_array($objectType, DAOActions::$ACTION_TYPE)) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        if ($objectType === DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"]) {
            $data = $this->getPostData($objectId);
            delete_post_meta($objectId, '_tsm_first_visit_after_translation');
        } else if ($objectType === DAOActions::$ACTION_TYPE["EMAIL"]) {
            $data = $this->getEmailData($objectId);
            delete_option('_tsm_first_visit_after_translation_emails');
        } else if ($objectType === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            $data = $this->getModelElementorData($objectId);
        }
        wp_send_json_success($data);
        wp_die();
    }

    private function getPostData($postId)
    {
        global $tsm;

        $data = [];
        $translations = TranslationPost::findTranslationFor($postId);
        $data['translations'] = $translations->getTranslations();
        $data['firstVisitAfterTSMTranslatedIt'] = get_post_meta($postId, '_tsm_first_visit_after_translation', true);
        $data['hasTSMTranslatedIt'] = get_post_meta($postId, '_has_been_translated_by_tsm', true);
        $data['translatedFromSlug'] = get_post_meta($postId, '_translated_by_tsm_from', true);
        $data['summary'] = get_post_meta($postId, '_summary_translated_by_tsm', true);

        return $data;
    }

    private function getEmailData($emailId)
    {
        global $tsm;

        $data = [];
        $data["translations"] = [];
        $data['firstVisitAfterTSMTranslatedIt'] = get_option('_tsm_first_visit_after_translation_emails', false);
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        do_action("tsm-disable-emails-query-filter");
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            if ($email->id === $emailId) {
                $email->init_settings();
                foreach ($languages as $code => $language) {
                    if ($language["default"]) {
                        $data["translations"][$code] = [
                            "subject" => $email->get_option('subject') ?? $email->get_default_subject(),
                            "heading" => $email->get_option('heading') ?? $email->get_default_heading(),
                            "content" => $email->get_option('additional_content') ?? $email->get_default_additional_content(),
                            "estimatedQuota" => 0
                        ];
                    } else {
                        $temporaryAction = new Translatable\Emails\Action([
                            "objectId" => $email->id,
                            "slugTo" => $code,
                            "origin" => "HOOK"
                        ]);
                        $estimatedQuota = $temporaryAction->getEstimatedQuota();
                        $data["translations"][$code] = [
                            "subject" => $email->settings["tsm-language-" . $code . "-subject"],
                            "heading" => $email->settings["tsm-language-" . $code . "-header"],
                            "additional_content" => $email->settings["tsm-language-" . $code . "-content"],
                            "estimatedQuota" => $estimatedQuota
                        ];
                    }
                }
                break;
            }
        }
        do_action("tsm-enable-emails-query-filter");
        return $data;
    }

    private function getModelElementorData($modelId)
    {
        return [];
    }
}

$GetVariables = new GetVariables();
$GetVariables->init();