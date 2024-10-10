<?php

namespace TraduireSansMigraine\Wordpress\Hooks\Posts;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

class OnDeletionPosts
{
    private $count = 0;
    private $ids = [];

    public function init()
    {
        add_action('admin_init', [$this, 'handleTrashed']);
        add_filter('bulk_post_updated_messages', [$this, 'updateBulksMessages'], 10, 2);
    }


    public function handleTrashed()
    {
        global $_REQUEST;

        if (isset($_REQUEST['trashed']) && isset($_REQUEST['ids'])) {
            $ids = explode(",", preg_replace('/[^0-9,]/', '', $_REQUEST['ids']));
            foreach ($ids as $id) {
                if (!current_user_can('edit_post', $id)) {
                    continue;
                }
                $this->trashedTranslationsIfNeeded($id);
            }
        }
    }

    private function trashedTranslationsIfNeeded($postId)
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["autoDeletionTranslations"])) {
            return;
        }
        $translations = $tsm->getPolylangManager()->getAllTranslationsPost($postId);
        $currentSlug = $tsm->getPolylangManager()->getLanguageForPost($postId);
        $isDefault = false;
        foreach ($translations as $translation) {
            if (($translation["postId"] == $postId || $translation["code"] === $currentSlug) && $translation["default"]) {
                $isDefault = true;
                break;
            }
        }
        if (!$isDefault) {
            return;
        }
        $this->deleteTranslations($translations);
    }

    private function deleteTranslations($translations)
    {
        foreach ($translations as $translation) {
            if ($translation["default"] || empty($translation["postId"])) {
                continue;
            }
            $this->count++;
            wp_trash_post($translation["postId"]);
            $this->ids[] = $translation["postId"];
        }
    }

    public function updateBulksMessages($bulk_messages)
    {
        global $bulk_counts;
        if (!isset($bulk_counts)) {
            $bulk_counts = [];
        }
        if (isset($bulk_counts['trashed']) && isset($_REQUEST['ids'])) {
            $bulk_counts['trashed'] += $this->count;
            $ids = explode(",", preg_replace('/[^0-9,]/', '', sanitize_text_field($_REQUEST['ids'])));
            $_REQUEST['ids'] = implode(',', array_merge($ids, $this->ids));
        }
        if (isset($bulk_messages['post']['trashed']) && $this->count > 0) {
            $addingMessage = ' (' . TextDomain::_n("including %s translation", "including %s translations", $this->count, $this->count) . ' 🦦)';
            $bulk_messages['post']['trashed'] = preg_replace('/\.$/', $addingMessage . '.', $bulk_messages['post']['trashed']);
        }

        return $bulk_messages;
    }
}

$OnDeletionPosts = new OnDeletionPosts();
$OnDeletionPosts->init();