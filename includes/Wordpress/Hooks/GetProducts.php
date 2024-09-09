<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetProducts {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_products", [$this, "getProducts"]);
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

    public function getProducts() {
        global $tsm;
        if (!isset($_GET["wpNonce"])  || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $products = $tsm->getClient()->getProducts();
        wp_send_json_success([
            "products" => $products
        ]);
        wp_die();
    }
}
$GetProducts = new GetProducts();
$GetProducts->init();