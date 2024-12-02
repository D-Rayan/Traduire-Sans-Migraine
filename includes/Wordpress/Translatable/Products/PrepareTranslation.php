<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Products;

use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

class PrepareTranslation extends Translatable\Posts\PrepareTranslation
{
    public function prepareDataToTranslate()
    {
        parent::prepareDataToTranslate();
        $this->addChildrenPosts();
    }

    private function addChildrenPosts()
    {
        global $wpdb;
        $postsChildren = $wpdb->get_results($wpdb->prepare("SELECT * FROM $wpdb->posts WHERE post_parent = %d AND post_type NOT IN ('revision', 'attachment', 'auto-draft')", $this->object->ID));
        foreach ($postsChildren as $postChild) {
            if (!empty($postChild->post_title)) {
                $this->dataToTranslate["child_post_title_" . $postChild->ID] = $postChild->post_title;
            }
            if (!empty($postChild->post_content)) {
                $this->dataToTranslate["child_post_content_" . $postChild->ID] = $postChild->post_content;
            }
            if (!empty($postChild->post_name)) {
                $this->dataToTranslate["child_post_name_" . $postChild->ID] = $postChild->post_name;
            }
            if (!empty($postChild->post_excerpt)) {
                $this->dataToTranslate["child_post_excerpt_" . $postChild->ID] = $postChild->post_excerpt;
            }
        }
    }
}