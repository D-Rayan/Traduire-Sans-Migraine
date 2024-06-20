<?php

namespace TraduireSansMigraine\Wordpress;

class PostsSearch {
    public function __construct() {

    }

    public function hideUntranslatedPosts($where) {
        if (!is_admin()) {
            return $where;
        }
        $addingClause = " AND (post_title NOT LIKE '%Translation of post%' OR post_content != 'This content is temporary... It will be either deleted or updated soon.' OR post_status != 'draft')";
        return $where . $addingClause;
    }

    public function init() {
        add_action("posts_where", [$this, "hideUntranslatedPosts"]);
    }
    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new PostsSearch();
        }
        return self::$instance;
    }
}