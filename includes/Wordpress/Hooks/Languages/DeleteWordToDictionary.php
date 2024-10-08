<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Languages;

use TraduireSansMigraine\SeoSansMigraine\Client;

if (!defined("ABSPATH")) {
    exit;
}

class DeleteWordToDictionary
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
        add_action("wp_ajax_traduire-sans-migraine_delete_word_to_dictionary", [$this, "deleteWordToDictionary"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function deleteWordToDictionary()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["id"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $id = $_GET["id"];
        $client = Client::getInstance();
        $response = $client->deleteWordFromDictionary($id);
        if ($response === false) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        wp_send_json_success([]);
        wp_die();
    }
}

$DeleteWordToDictionary = new DeleteWordToDictionary();
$DeleteWordToDictionary->init();