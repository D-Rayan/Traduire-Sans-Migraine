<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation {
    private $clientSeoSansMigraine;
    private $languageManager;
    public function __construct()
    {
        $this->clientSeoSansMigraine = Client::getInstance();
        $this->languageManager = new LanguageManager();
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_prepare_translate", [$this, "prepareTranslation"]);
    }

    public function loadHooks() {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }
    public function init() {
        $this->loadHooks();
    }

    public function prepareTranslation() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine_editor_prepare_translate")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["post_id"]) || !isset($_POST["languages"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post or the languages asked. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $result = $this->prepareTranslationExecute($_POST["post_id"], $_POST["languages"]);
        if ($result["success"]) {
            wp_send_json_success($result["data"]);
        } else {
            wp_send_json_error($result["data"], 400);
        }
        wp_die();
    }

    public function prepareTranslationExecute($postId, $languages) {
        global $wpdb;

        $result = $this->clientSeoSansMigraine->checkCredential();
        if (!$result) {
            return [
                "success" => false,
                "data" => [
                    "title" => TextDomain::__("An error occurred"),
                    "message" => TextDomain::__("We could not authenticate you. Please check the plugin settings."),
                    "logo" => "loutre_triste.png"
                ]
            ];
        }
        $originalTranslations = $this->languageManager->getLanguageManager()->getAllTranslationsPost($postId);
        $translations = [];
        $originalPost = get_post($postId);
        $createdOnes = [];

        foreach ($originalTranslations as $slug => $translation) {
            if ($translation["postId"]) {
                $translations[$slug] = $translation["postId"];
            } else if (in_array($slug, $languages)) {
                $temporaryNamePost = "post-" . $postId . "-" . $slug."-traduire-sans-migraine";
                $query = $wpdb->prepare('SELECT ID FROM ' . $wpdb->posts . ' WHERE post_name = %s', $temporaryNamePost);
                $exists = $wpdb->get_var($query);
                if (!empty($exists)) {
                    $temporaryNamePost .= "-" . time();
                }

                $createdOnes[$slug] = true;
                $translations[$slug] = wp_insert_post([
                    'post_title' => "Translation of post " . $postId . " in " . $slug,
                    'post_content' => "This content is temporary... It will be either deleted or updated soon.",
                    'post_author' => $originalPost->post_author,
                    'post_type' => $originalPost->post_type,
                    'post_name' => $temporaryNamePost,
                    'post_status' => 'draft'
                ], true);
            }
        }

        $this->languageManager->getLanguageManager()->saveAllTranslationsPost($translations);
        $updatedTranslations = $this->languageManager->getLanguageManager()->getAllTranslationsPost($postId);
        $errorCreationTranslations = false;
        $data = ["wpNonce" => []];
        foreach ($languages as $slug) {
            $data["wpNonce"][$slug] = wp_create_nonce("traduire-sans-migraine_editor_start_translate_" . $slug);
            if (isset($updatedTranslations[$slug]["postId"]) && !empty(isset($updatedTranslations[$slug]["postId"]))) {
                continue;
            }
            $errorCreationTranslations = true;
            if (isset($translations[$slug]) && isset($createdOnes[$slug])) { wp_delete_post($translations[$slug], true); }
        }

        if ($errorCreationTranslations) {
            return [
                "success" => false,
                "data" => [
                    "title" => TextDomain::__("An error occurred"),
                    "message" => TextDomain::__("We could not create all the translations. Please try again."),
                    "logo" => "loutre_triste.png"
                ]
            ];
        }
        return ["success" => true, "data" => $data];
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}