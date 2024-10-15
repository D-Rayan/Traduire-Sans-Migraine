<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Wordpress\Object\Action;

if (!defined("ABSPATH")) {
    exit;
}

class GetPosts
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
        add_action("wp_ajax_traduire-sans-migraine_get_posts", [$this, "getPosts"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getPosts()
    {
        global $tsm;
        if (!isset($_POST["wpNonce"]) || !wp_verify_nonce($_POST["wpNonce"], "traduire-sans-migraine")) {
            wp_send_json_error(seoSansMigraine_returnNonceError(), 400);
            wp_die();
        }
        if (!isset($_POST["from"])) {
            wp_send_json_error(seoSansMigraine_returnErrorIsset(), 400);
            wp_die();
        }
        $slugFrom = $_POST["from"];
        $sortField = isset($_GET["sortField"]) && $_GET["sortField"] === "post_title" ? "post_title" : "ID";
        $sortOrder = isset($_GET["sortOrder"]) && $_GET["sortOrder"] === "ascend" ? "ASC" : "DESC";
        $postAuthors = $this->getAuthorsIDFromDB();
        $postStatus = ["publish", "draft", "future", "private"];
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$slugFrom])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
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

    private function getAuthorsIDFromDB()
    {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_status IN ('publish', 'draft')");
        $authors = [];
        foreach ($posts as $post) {
            $authorId = $post->post_author;
            $authors[] = $authorId;
        }
        return $authors;
    }

    private function searchPosts($fromTermId, $authors = [], $postStatus = [], $sortField = "ID", $sortOrder = "DESC")
    {
        global $wpdb;
        $queryFetchPosts = "SELECT posts.ID, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
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
                            trFrom.term_taxonomy_id = $fromTermId 
                        ORDER BY posts.$sortField $sortOrder
                        ";


        return $wpdb->get_results($queryFetchPosts);
    }

    private function filterPosts($posts, $languagesTranslated)
    {
        $filteredPosts = [];
        foreach ($posts as $post) {
            if (empty($post->post_title)) {
                continue;
            }
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
                $aRelatedActionExist = Action::loadByPostId($post->ID, $slug);
                $translationIsUpdated = $translation && $translation->post_modified > $post->post_modified;

                $shouldKeepIt = $shouldBeDone && $isTranslated && $translationIsUpdated;
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotTranslated && !$isTranslated && (!$aRelatedActionExist || !$aRelatedActionExist->willBeProcessing()));
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
}

$GetPosts = new GetPosts();
$GetPosts->init();