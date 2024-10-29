<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

if (!defined("ABSPATH")) {
    exit;
}

class GetAuthors
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
        add_action("wp_ajax_traduire-sans-migraine_get_authors", [$this, "getAuthors"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getAuthors()
    {
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }

        wp_send_json_success([
            "authors" => $this->getAuthorsFromDB()
        ]);
        wp_die();
    }

    private function getAuthorsFromDB()
    {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type IN ('page', 'post', 'elementor_library')");
        $authors = [];
        foreach ($posts as $post) {
            $authorId = $post->post_author;
            $author = get_userdata($authorId);
            if ($author === false) {
                $authors[] = [
                    "ID" => $authorId,
                    "name" => "Unknown"
                ];
                continue;
            }
            $authors[] = [
                "ID" => $authorId,
                "name" => $author->display_name
            ];
        }
        return $authors;
    }

}

$GetAuthors = new GetAuthors();
$GetAuthors->init();