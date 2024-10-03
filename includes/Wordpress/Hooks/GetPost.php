<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Action;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetPost {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_post", [$this, "getPost"]);
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

    public function getPost() {
        global $tsm;
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["postId"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The parameter postId is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $post = get_post($_GET["postId"], ARRAY_A);
        if (empty($post)) {
            wp_send_json_error([
                "message" => TextDomain::__("The post does not exist"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $post["translations"] = $tsm->getPolylangManager()->getAllTranslationsPost($post["ID"]);
        wp_send_json_success([
            "post" => $post
        ]);
        wp_die();
    }
}
$GetPost = new GetPost();
$GetPost->init();