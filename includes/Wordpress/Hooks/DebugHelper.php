<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\PolylangManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class DebugHelper {

    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_debug", [$this, "debugTranslation"]);
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

    public function debugTranslation() {
        global $tsm;
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["post_id"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not find the post asked. Please try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $code = $_GET["code"];
        $originalPost = get_post($postId);
        $postMetas = get_post_meta($originalPost->ID);

        $result = $tsm->getClient()->checkCredential();
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
        $codeFrom = $tsm->getPolylangManager()->getLanguageForPost($originalPost->ID);
        $pluginsActives = get_option("active_plugins");
        $response = $tsm->getClient()->sendDebugData([
            "post" => $originalPost,
            "postMetas" => $postMetas,
            "codeFrom" => $codeFrom,
            "pluginsActives" => $pluginsActives,
            "code" => $code
        ]);
        if ($response["success"]) {
            wp_send_json_success([
                "title" => TextDomain::__("Debug data sent"),
                "message" => TextDomain::__("The debug data has been sent to the developers. Thank you for your help!"),
                "logo" => "loutre_docteur_no_shadow.png"
            ]);
        } else {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("We could not send the debug data. Please try again. Verify the code you entered."),
                "logo" => "loutre_triste.png"
            ], 400);
        }

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