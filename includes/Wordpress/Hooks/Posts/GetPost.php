<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

if (!defined("ABSPATH")) {
    exit;
}

class GetPost
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
        add_action("wp_ajax_traduire-sans-migraine_get_post", [$this, "getPost"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getPost()
    {
        global $tsm;
        if (!isset($_GET["wpNonce"]) || !wp_verify_nonce($_GET["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_GET["postId"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $post = get_post($_GET["postId"], ARRAY_A);
        if (empty($post)) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 404);
            wp_die();
        }
        $post["translations"] = [];
        $post["currentSlug"] = $tsm->getPolylangManager()->getLanguageSlugForPost($post["ID"]);
        foreach ($tsm->getPolylangManager()->getAllTranslationsPost($post["ID"]) as $slug => $data) {
            $translationPost = $this->getTranslationPostData($post, $data, get_the_terms($post["ID"], "category"));
            $post["translations"][$slug] = $translationPost;
        }
        wp_send_json_success([
            "post" => $post
        ]);
        wp_die();
    }

    private function getTranslationPostData($post, $translationPost, $termsCategories)
    {
        global $tsm;
        $postExists = $translationPost["postId"] && get_post_status($translationPost["postId"]) !== "trash";
        $issuesTranslatedUrls = $tsm->getLinkManager()->getIssuedInternalLinks($post["post_content"], $post["currentSlug"], $translationPost["code"]);
        $notTranslated = $issuesTranslatedUrls["notTranslated"];
        $notPublished = $issuesTranslatedUrls["notPublished"];
        $missingCategories = [];
        if (is_array($termsCategories)) {
            foreach ($termsCategories as $termCategory) {
                $result = $tsm->getPolylangManager()->getTranslationCategories([$termCategory->term_id], $translationPost["code"]);
                if (empty($result)) {
                    $missingCategories[] = $termCategory->name;
                }
            }
        }
        return [
            "name" => $translationPost["name"],
            "code" => $translationPost["code"],
            "postId" => $postExists ? $translationPost["postId"] : null,
            "issues" => [
                "urlNotTranslated" => $notTranslated,
                "urlNotPublished" => $notPublished,
                "categoriesNotTranslated" => $missingCategories
            ],
        ];
    }
}

$GetPost = new GetPost();
$GetPost->init();