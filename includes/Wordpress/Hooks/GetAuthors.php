<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetAuthors
{
    public function __construct()
    {
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function loadHooksAdmin()
    {
        add_action("wp_ajax_traduire-sans-migraine_get_authors", [$this, "getAuthors"]);
    }

    public function loadHooks()
    {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }

    public function init()
    {
        $this->loadHooks();
    }

    public function getAuthors()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }

        wp_send_json_success([
            "authors" => $this->getAuthorsFromDB()
        ]);
        wp_die();
    }

    private function getAuthorsFromDB() {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status IN ('publish', 'draft')");
        $authors = [];
        foreach ($posts as $post) {
            $authorId = $post->post_author;
            $author = get_userdata($authorId);
            if ($author === false) {
                $authors[$authorId] = [
                    "ID" => $authorId,
                    "name" => "Unknown"
                ];
                continue;
            }
            $authors[$authorId] = [
                "ID" => $authorId,
                "name" => $author->display_name
            ];
        }
        return $authors;
    }

}

$GetAuthors = new GetAuthors();
$GetAuthors->init();