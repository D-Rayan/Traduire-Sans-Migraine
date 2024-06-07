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

class TranslationState {
    public function __construct()
    {

    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_get_state_translate", [$this, "getTranslateState"]);
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

    public function getTranslateState() {
        if (!isset($_GET["tokenId"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the request ID."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $tokenId = $_GET["tokenId"];
        $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
            "percentage" => 25,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "message" => [
                "id" => TextDomain::_f("We will create and translate your post 💡"),
                "args" => []
            ]
        ]);
        if (!$state) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the state of the translation"),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        if (isset($state["status"]) && $state["status"] === Step::$STEP_STATE["DONE"]) {
            delete_option("_seo_sans_migraine_state_" . $tokenId);
            delete_option("_seo_sans_migraine_postId_" . $tokenId);
        }
        if (isset($state["message"])) {
            $state["html"] = TextDomain::__($state["message"]["id"], ...$state["message"]["args"]);
        }
        echo json_encode(["success" => true, "data" => $state]);
        wp_die();
    }
    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}