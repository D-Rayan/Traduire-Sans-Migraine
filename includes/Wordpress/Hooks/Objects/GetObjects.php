<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Objects;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

if (!defined("ABSPATH")) {
    exit;
}

class GetObjects
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
        add_action("wp_ajax_traduire-sans-migraine_get_objects", [$this, "getObjects"]);
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function getObjects()
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
        $allowedObjectTypes = [
            DAOActions::$ACTION_TYPE["TERMS"],
            DAOActions::$ACTION_TYPE["POST_PAGE"]
        ];
        if ($tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])) {
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["EMAIL"];
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["PRODUCT"];
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["ATTRIBUTES"];
        }
        if (is_plugin_active("elementor/elementor.php")) {
            $allowedObjectTypes[] = DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"];
        }
        $slugFrom = $_POST["from"];
        $objectType = isset($_POST["objectType"]) && in_array($_POST["objectType"], $allowedObjectTypes) ? $_POST["objectType"] : "POST_PAGE";
        $pageSize = isset($_GET["pageSize"]) && is_numeric($_GET["pageSize"]) ? intval($_GET["pageSize"]) : 10;
        $page = isset($_GET["page"]) && is_numeric($_GET["page"]) ? intval($_GET["page"]) : 1;
        $offset = ($page - 1) * $pageSize;
        $sortField = isset($_GET["sortField"]) && $_GET["sortField"] === "post_title" ? "post_title" : "ID";
        $sortOrder = isset($_GET["sortOrder"]) && $_GET["sortOrder"] === "ascend" ? "ASC" : "DESC";
        $postAuthors = $this->getAuthorsIDFromDB();
        $postStatus = ["publish", "draft", "future", "private"];
        $enabled = ["no", "yes"];
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        if (!isset($languages[$slugFrom])) {
            wp_send_json_error(seoSansMigraine_returnErrorForImpossibleReasons(), 400);
            wp_die();
        }
        $onlyWoocommerce = false;
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
        $taxonomies = ["category", "post_tag", "product_cat", "product_tag", "attribute"];
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
                    case "enabled":
                        $enabled = array_intersect($enabled, $filterValue);
                        break;
                    case "taxonomy":
                        $taxonomies = array_intersect($taxonomies, $filterValue);
                        break;
                    case "woocommerce":
                        if (in_array("yes", $filterValue)) {
                            $taxonomies = array_intersect($taxonomies, ["product_cat", "product_tag", "attribute"]);
                            $onlyWoocommerce = true;
                        }
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
        switch ($objectType) {
            case DAOActions::$ACTION_TYPE["POST_PAGE"]:
                if ($onlyWoocommerce) {
                    $posts = $this->getWoocommercePages($fromTermId);
                    $totalObjects = count($posts);
                } else {
                    $posts = $this->searchPosts($fromTermId, $postAuthors, $postStatus, $sortField, $sortOrder, $offset, $pageSize, ["page", "post"]);
                    $totalObjects = $this->countPosts($fromTermId, $postAuthors, $postStatus, ["page", "post"]);
                }
                $objects = $this->populatePosts($posts, $languagesTranslated, $objectType);
                break;
            case DAOActions::$ACTION_TYPE["PRODUCT"]:
                $posts = $this->searchPosts($fromTermId, $postAuthors, $postStatus, $sortField, $sortOrder, $offset, $pageSize, ["product"]);
                $objects = $this->populatePosts($posts, $languagesTranslated, $objectType);
                $totalObjects = $this->countPosts($fromTermId, $postAuthors, $postStatus, ["product"]);
                break;
            case DAOActions::$ACTION_TYPE["EMAIL"]:
                $objects = $this->getAllEmails($languagesTranslated, $enabled);
                $totalObjects = count($objects);
                $objects = array_slice($objects, $offset, $pageSize);
                break;
            case DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]:
                $posts = $onlyWoocommerce ? [] : $this->searchPosts($fromTermId, $postAuthors, $postStatus, $sortField, $sortOrder, $offset, $pageSize, ["model_elementor"]);
                $objects = $this->populatePosts($posts, $languagesTranslated, $objectType);
                $totalObjects = $onlyWoocommerce ? 0 : $this->countPosts($fromTermId, $postAuthors, $postStatus, ["model_elementor"]);
                break;
            case DAOActions::$ACTION_TYPE["TERMS"]:
                $fromTermId = LanguageTerm::getTermTaxonomyId(LanguageTerm::getTermIdByLanguage($fromTermId));
                $objects = $this->searchTerms($fromTermId, $languagesTranslated, $taxonomies, $offset, $pageSize);
                $totalObjects = $this->countTerms($fromTermId, $taxonomies);
                break;
            case DAOActions::$ACTION_TYPE["ATTRIBUTES"]:
                $objects = $this->getAttributes($fromTermId, $languagesTranslated, $offset, $pageSize);
                $totalObjects = count(wc_get_attribute_taxonomies());
                break;
        }
        wp_send_json_success([
            "objects" => $objects,
            "pagination" => [
                "total" => $totalObjects,
                "pageSize" => $pageSize,
                "current" => $page,
            ],
        ]);
        wp_die();
    }

    private function getAuthorsIDFromDB()
    {
        global $wpdb;
        $posts = $wpdb->get_results("SELECT DISTINCT post_author FROM $wpdb->posts WHERE post_type IN ('page', 'post', 'model_elementor', 'product')");
        $authors = [];
        foreach ($posts as $post) {
            $authorId = $post->post_author;
            $authors[] = $authorId;
        }
        return $authors;
    }

    private function getWoocommercePages($fromTermId)
    {
        global $tsm, $wpdb;
        $woocommercePages = [
            get_option('woocommerce_shop_page_id'),
            get_option('woocommerce_cart_page_id'),
            get_option('woocommerce_checkout_page_id'),
            get_option('woocommerce_myaccount_page_id'),
            get_option('woocommerce_terms_page_id')
        ];

        $pagesId = [];
        foreach ($tsm->getPolylangManager()->getLanguagesActives() as $slug => $language) {
            if ($language["id"] !== $fromTermId) {
                continue;
            }
            foreach ($woocommercePages as $originalId) {
                $translationId = $this->getWoocommercePageTranslation($originalId, $language);
                if (empty($translationId)) {
                    continue;
                }
                $pagesId[] = $translationId;
            }
            break;
        }
        $queryFetchPosts = "SELECT posts.ID, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM $wpdb->term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap FROM $wpdb->posts posts
                        WHERE posts.ID IN (" . implode(",", $pagesId) . ")
                        ";
        return $wpdb->get_results($queryFetchPosts);
    }

    private function getWoocommercePageTranslation($id, $language)
    {
        $translations = TranslationPost::findTranslationFor($id);
        return $translations->getTranslation($language["code"]);
    }

    private function searchPosts($fromTermId, $authors = [], $postStatus = [], $sortField = "ID", $sortOrder = "DESC", $offset = 0, $limit = 50, $postTypes = ["page", "post"])
    {
        global $wpdb;
        $queryFetchPosts = "SELECT posts.ID, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM $wpdb->term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap FROM $wpdb->posts posts
                        LEFT JOIN $wpdb->term_relationships trFrom ON ID = trFrom.object_id 
                        WHERE 
                            posts.post_type IN ('" . implode("','", $postTypes) . "') AND 
                            posts.post_status IN ('" . implode("','", $postStatus) . "') AND 
                            posts.post_author IN (" . implode(",", $authors) . ") AND 
                            trFrom.term_taxonomy_id = $fromTermId AND
                            posts.post_title != ''
                        ORDER BY posts.$sortField $sortOrder
                        LIMIT $offset, $limit
                        ";


        return $wpdb->get_results($queryFetchPosts);
    }

    private function countPosts($fromTermId, $authors = [], $postStatus = [], $postTypes = ["page", "post"])
    {
        global $wpdb;
        $queryFetchPosts = "SELECT COUNT(*) FROM $wpdb->posts posts
                        LEFT JOIN $wpdb->term_relationships trFrom ON ID = trFrom.object_id 
                        WHERE 
                            posts.post_type IN ('" . implode("','", $postTypes) . "') AND 
                            posts.post_status IN ('" . implode("','", $postStatus) . "') AND 
                            posts.post_author IN (" . implode(",", $authors) . ") AND 
                            trFrom.term_taxonomy_id = $fromTermId AND
                            posts.post_title != ''
                        ";


        return $wpdb->get_var($queryFetchPosts);
    }

    private function populatePosts($posts, $languagesTranslated, $actionType)
    {
        $filteredPosts = [];
        foreach ($posts as $post) {
            $translationMap = !empty($post->translationMap) ? unserialize($post->translationMap) : [];
            $post->translationMap = [];
            $post->filters = [
                "display" => true
            ];
            foreach ($languagesTranslated as $slug => $status) {
                $shouldBeDone = in_array("done", $status);
                $shouldBeNotTranslated = in_array("not_translated", $status);
                $shouldBeNotUpdated = in_array("not_updated", $status);

                $translationId = isset($translationMap[$slug]) ? $translationMap[$slug] : null;
                $translation = $translationId ? get_post($translationId) : null;
                $isTranslated = !empty($translation);
                $aRelatedActionExist = AbstractAction::loadByObjectId($post->ID, $slug, $actionType);
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
                $post->filters["display"] = $post->filters["display"] && $shouldKeepIt;
            }
            $filteredPosts[] = $post;
        }
        return $filteredPosts;
    }

    private function getAllEmails($languagesTranslated, $enabled)
    {
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $emails = wc()->mailer()->get_emails();
        $filteredEmails = [];
        foreach ($emails as $email) {
            $email->init_settings();
            if (!in_array(empty($email->enabled) ? "no" : $email->enabled, $enabled)) {
                continue;
            }
            $email->filters = [
                "display" => true
            ];
            $email->translationMap = [];
            foreach ($languagesTranslated as $slug => $status) {
                $shouldBeDone = in_array("done", $status);
                $shouldBeNotTranslated = in_array("not_translated", $status);
                $shouldBeNotUpdated = in_array("not_updated", $status);
                $aRelatedActionExist = AbstractAction::loadByObjectId($email->id, $slug, DAOActions::$ACTION_TYPE["EMAIL"]);

                $isTranslated = isset($email->settings["tsm-language-" . $slug . "-updatedTime"]);
                $translationIsUpdated = $isTranslated && (!isset($email->settings["updatedTime"]) || $email->settings["tsm-language-" . $slug . "-updatedTime"] > $email->settings["updatedTime"]);

                $shouldKeepIt = $shouldBeDone && $isTranslated && $translationIsUpdated;
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotTranslated && !$isTranslated && (!$aRelatedActionExist || !$aRelatedActionExist->willBeProcessing()));
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotUpdated && $isTranslated && !$translationIsUpdated);
                $shouldKeepIt = $shouldKeepIt || ($shouldBeDone && $isTranslated && $translationIsUpdated);

                $email->translationMap[$slug] = [
                    "translationExists" => $isTranslated,
                    "translationIsUpdated" => $translationIsUpdated,
                ];
                $email->filters["display"] = $email->filters["display"] && $shouldKeepIt;
            }
            $email->emailId = $email->id;
            foreach ($languages as $code => $language) {
                if ($language["default"]) {
                    $email->$code = [
                        "subject" => $email->settings["subject"] ?? $email->get_default_subject(),
                        "heading" => $email->settings["heading"] ?? $email->get_default_heading(),
                        "additional_content" => $email->settings["additional_content"] ?? $email->get_default_additional_content(),
                    ];
                } else {
                    $email->$code = [
                        "subject" => $email->settings["tsm-language-" . $code . "-subject"] ?? "",
                        "heading" => $email->settings["tsm-language-" . $code . "-header"] ?? "",
                        "additional_content" => $email->settings["tsm-language-" . $code . "-content"] ?? "",
                    ];
                }
            }
            $filteredEmails[] = $email;
        }
        return $filteredEmails;
    }

    private function searchTerms($fromTermId, $languagesTranslated, $taxonomies, $offset, $pageSize)
    {
        $terms = $this->getTerms($this->getTaxonomies($taxonomies), $fromTermId, $offset, $pageSize);
        $filteredTerms = [];
        foreach ($terms as $term) {
            $termData = [
                "ID" => $term->term_id,
                "name" => $term->name,
                "slug" => $term->slug,
                "description" => $term->description,
                "taxonomy" => $term->taxonomy,
                "filters" => [
                    "display" => true
                ],
                "translationMap" => []
            ];
            $translations = TranslationTerms::findTranslationFor($term->term_id);
            foreach ($languagesTranslated as $slug => $status) {
                $shouldBeDone = in_array("done", $status);
                $shouldBeNotTranslated = in_array("not_translated", $status);
                $shouldBeNotUpdated = in_array("not_updated", $status);
                $aRelatedActionExist = AbstractAction::loadByObjectId($termData["ID"], $slug, DAOActions::$ACTION_TYPE["TERMS"]);

                $isTranslated = !empty($translations->getTranslation($slug));
                $translationIsUpdated = $isTranslated; // @todo

                $shouldKeepIt = $shouldBeDone && $isTranslated && $translationIsUpdated;
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotTranslated && !$isTranslated && (!$aRelatedActionExist || !$aRelatedActionExist->willBeProcessing()));
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotUpdated && $isTranslated && !$translationIsUpdated);
                $shouldKeepIt = $shouldKeepIt || ($shouldBeDone && $isTranslated && $translationIsUpdated);

                $termData["translationMap"][$slug] = [
                    "translationExists" => $isTranslated,
                    "translationIsUpdated" => $translationIsUpdated,
                ];
                $termData["filters"]["display"] = $termData["filters"]["display"] && $shouldKeepIt;
            }
            $filteredTerms[] = $termData;
        }
        return $filteredTerms;
    }

    private function getTerms($taxonomies, $fromLanguageId, $offset, $pageSize)
    {
        global $wpdb;
        return $wpdb->get_results(
            $wpdb->prepare(
                "SELECT t.*, tt.taxonomy, tt.description, tt.parent, tt.count, tt.term_taxonomy_id
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                INNER JOIN {$wpdb->term_relationships} tr ON tt.term_id = tr.object_id
                WHERE tt.taxonomy IN ('" . implode("','", $taxonomies) . "') AND
                      tr.term_taxonomy_id = %d
                LIMIT %d, %d",
                $fromLanguageId, $offset, $pageSize
            )
        );
    }

    private function getTaxonomies($taxonomies)
    {
        $taxonomiesCompleted = array_intersect($taxonomies, ["category", "post_tag", "product_cat", "product_tag"]);
        if (in_array("attribute", $taxonomies)) {
            $attributes = wc_get_attribute_taxonomies();
            foreach ($attributes as $attribute) {
                $attributeName = "pa_" . str_replace("pa_", "", $attribute->attribute_name);
                $taxonomiesCompleted[] = $attributeName;
            }
        }
        return $taxonomiesCompleted;
    }

    private function countTerms($fromLanguageId, $taxonomies)
    {
        global $wpdb;

        return $wpdb->get_var(
            $wpdb->prepare(
                "SELECT COUNT(*)
                FROM {$wpdb->terms} t
                INNER JOIN {$wpdb->term_taxonomy} tt ON t.term_id = tt.term_id
                INNER JOIN {$wpdb->term_relationships} tr ON tt.term_id = tr.object_id
                WHERE tt.taxonomy IN ('" . implode("','", $this->getTaxonomies($taxonomies)) . "') AND
                      tr.term_taxonomy_id = %d",
                $fromLanguageId
            )
        );
    }

    private function getAttributes($fromTermId, $languagesTranslated, $offset, $pageSize)
    {
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        foreach ($languages as $slug => $language) {
            if ($language["id"] === $fromTermId) {
                $fromLanguage = $language;
                break;
            }
        }
        $attributes = wc_get_attribute_taxonomies();
        $filteredAttributes = [];
        foreach ($attributes as $attribute) {
            if (count($filteredAttributes) >= $pageSize) {
                break;
            }
            $translations = TranslationAttribute::findTranslationFor($attribute->attribute_id);
            $attributeData = [
                "ID" => $attribute->attribute_id,
                "name" => empty($translations->getTranslation($fromLanguage["code"])) ? $attribute->attribute_label : $translations->getTranslation($fromLanguage["code"]),
                "filters" => [
                    "display" => true
                ],
                "translationMap" => []
            ];
            foreach ($languagesTranslated as $slug => $status) {
                $shouldBeDone = in_array("done", $status);
                $shouldBeNotTranslated = in_array("not_translated", $status);
                $shouldBeNotUpdated = in_array("not_updated", $status);
                $aRelatedActionExist = false; // AbstractAction::loadByObjectId($attributeData["ID"], $slug, DAOActions::$ACTION_TYPE["T"]);

                $isTranslated = !empty($translations->getTranslation($slug)) || $languages[$slug]["default"];
                $translationIsUpdated = $isTranslated; // @todo

                $shouldKeepIt = $shouldBeDone && $isTranslated && $translationIsUpdated;
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotTranslated && !$isTranslated && (!$aRelatedActionExist || !$aRelatedActionExist->willBeProcessing()));
                $shouldKeepIt = $shouldKeepIt || ($shouldBeNotUpdated && $isTranslated && !$translationIsUpdated);
                $shouldKeepIt = $shouldKeepIt || ($shouldBeDone && $isTranslated && $translationIsUpdated);

                $attributeData["translationMap"][$slug] = [
                    "translationExists" => $isTranslated,
                    "translationIsUpdated" => $translationIsUpdated,
                ];
                $attributeData["filters"]["display"] = $attributeData["filters"]["display"] && $shouldKeepIt;
            }
            if ($offset > 0) {
                $offset--;
                continue;
            }
            $filteredAttributes[] = $attributeData;
        }
        return $filteredAttributes;
    }
}

$GetPosts = new GetObjects();
$GetPosts->init();