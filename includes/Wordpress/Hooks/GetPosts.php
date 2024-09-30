<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class GetPosts {
    public function __construct()
    {
    }
    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_get_posts", [$this, "getPosts"]);
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

    public function getPosts() {
        global $tsm;
        if (!isset($_POST["wpNonce"])  || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_POST["from"])) {
            wp_send_json_error([
                "message" => TextDomain::__("The parameter from is missing"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $slugFrom = $_POST["from"];
        $sortField = isset($_GET["sortField"]) && $_GET["sortField"] === "post_title" ? "post_title" : "ID";
        $sortOrder = isset($_GET["sortOrder"]) && $_GET["sortOrder"] === "ascend" ? "ASC" : "DESC";
        $postAuthors = $this->getAuthorsIDFromDB();
        $postStatus = ["publish", "draft", "future"];
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$slugFrom])) {
            wp_send_json_error([
                "message" => TextDomain::__("The language from is not valid"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $languagesTranslated = [];
        foreach ($languages as $language) {
            if ($language["code"] === $slugFrom) {
                continue;
            }
            $languagesTranslated[$language["code"]] = [
                "done",
                "not_translated",
                "not_updated",
            ];
        }
        if (isset($_POST["filters"]) && is_array($_POST["filters"])) {
            foreach ($_POST["filters"] as $filterName => $filterValue) {
                if (!$filterValue) {
                    continue;
                }
                switch ($filterName) {
                    case "post_author":
                        $postAuthors = array_intersect($postAuthors, $filterValue);
                    break;
                    case "post_status":
                        $postStatus = array_intersect($postStatus, $filterValue);
                    break;
                    default:
                        if (!isset($languagesTranslated[$filterName])) {
                            break;
                        }
                        $languagesTranslated[$filterName] = array_intersect($languagesTranslated[$filterName], $filterValue);
                    break;
                }
            }
        }
        $fromTermId = $languages[$slugFrom]["id"];
        $posts = $this->searchPosts($fromTermId, $postAuthors, $postStatus, $sortField, $sortOrder);
        $posts = $this->filterPosts($posts, $languagesTranslated);
        wp_send_json_success([
            "posts" => $posts
        ]);
        wp_die();
    }

    private function filterPosts($posts, $languagesTranslated) {
        /*
         *
         */
        $Queue = Queue::getInstance();
        $filteredPosts = [];
        foreach ($posts as $post) {
            $translationMap = !empty($post->translationMap) ? unserialize($post->translationMap) : [];
            $shouldSkip = false;
            $post->translationMap = [];
            foreach ($languagesTranslated as $slug => $status) {
                $shouldBeDone = in_array("done", $status);
                $shouldBeNotTranslated = in_array("not_translated", $status);
                $shouldBeNotUpdated = in_array("not_updated", $status);

                $translationId = isset($translationMap[$slug]) ? $translationMap[$slug] : null;
                $translation = $translationId ? get_post($translationId) : null;
                $isTranslated = !empty($translation);
                $isInQueue = $Queue->isFromQueue($post->ID, $slug);
                $translationIsUpdated = $translation && $translation->post_modified > $post->post_modified;

                $shouldKeepIt = $shouldBeDone && $isTranslated && $translationIsUpdated;
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotTranslated && !$isTranslated && !$isInQueue);
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotUpdated && $isTranslated && !$translationIsUpdated);
                $shouldKeepIt = $shouldKeepIt || ($shouldBeDone && $isTranslated && $translationIsUpdated);

                $post->translationMap[$slug] = [
                    "translation" => $isTranslated ? [
                        "ID" => $translation->ID,
                        "title" => $translation->post_title,
                    ] : null,
                    "translationIsUpdated" => $translationIsUpdated,
                ];
                if (!$shouldKeepIt) {
                    $shouldSkip = true;
                    break;
                }
            }
            if ($shouldSkip) {
                continue;
            }
            $filteredPosts[] = $post;
        }
        return $filteredPosts;
    }
    private function searchPosts($fromTermId, $authors = [], $postStatus = [], $sortField = "ID", $sortOrder = "DESC") {
        global $wpdb;
        $queryFetchPosts = $wpdb->prepare(
            "SELECT posts.ID, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM wp_term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap FROM $wpdb->posts posts
                        LEFT JOIN $wpdb->term_relationships trFrom ON ID = trFrom.object_id 
                        WHERE 
                            posts.post_type IN ('page', 'post') AND 
                            posts.post_status IN ('" . implode("','", $postStatus) . "') AND 
                            posts.post_author IN (" . implode(",", $authors) . ") AND 
                            trFrom.term_taxonomy_id = $fromTermId AND 
                            (posts.post_title NOT LIKE '%Translation of post%' OR posts.post_content != 'This content is temporary... It will be either deleted or updated soon.' OR posts.post_status != 'draft')
                        ORDER BY posts.$sortField $sortOrder
                        "
        );

        return $wpdb->get_results($queryFetchPosts);
    }

    private function getAuthorsIDFromDB() {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status IN ('publish', 'draft')");
        $authors = [];
        foreach ($posts as $post) {
            $authorId = $post->post_author;
            $authors[] = $authorId;
        }
        return $authors;
    }
}
$GetPosts = new GetPosts();
$GetPosts->init();