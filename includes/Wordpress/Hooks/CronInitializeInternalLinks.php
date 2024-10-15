<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\Object\InternalsLinks;

if (!defined("ABSPATH")) {
    exit;
}

class CronInitializeInternalLinks
{
    private static $OPTION_NAME_LAST_POST_ID = "tsm-internal-links-cron-state";
    private static $CRON_NAME = "traduire_sans_migraine_cron_initialize_table";

    public function __construct()
    {
    }

    public static function getNextTimeRun()
    {
        return wp_next_scheduled(self::$CRON_NAME) - time();
    }

    public function init()
    {
        add_action('post_updated', [$this, "handlePostUpdated"], 10, 3);
        add_action(self::$CRON_NAME, [$this, "initializeTable"]);
    }

    public function enableCron()
    {
        add_filter('cron_schedules', function ($schedules) {
            $schedules['every_minute'] = array(
                'interval' => 60,
                'display' => __('Every minute')
            );

            return $schedules;
        });
        if (!wp_next_scheduled(self::$CRON_NAME)) {
            wp_schedule_event(time(), 'every_minute', self::$CRON_NAME);
        }
    }

    public function disableCron()
    {
        wp_clear_scheduled_hook(self::$CRON_NAME);
    }

    public function handlePostUpdated($postId, $postUpdated, $postBefore)
    {
        if ($postUpdated->post_content !== $postBefore->post_content && $this->postAlreadyBeenProcessed($postId)) {
            InternalsLinks::verifyPost($postUpdated);
        }
    }

    private function postAlreadyBeenProcessed($postId)
    {
        $stateCron = self::getOption();
        return (intval($stateCron["lastPostId"]) >= intval($postId));
    }

    public static function getOption()
    {
        return get_option(self::$OPTION_NAME_LAST_POST_ID, ["lastPostId" => 0, "lastExecuteTime" => 0, "lastCount" => 0]);
    }

    public function initializeTable()
    {
        $stateCron = self::getOption();
        $lastPostId = $stateCron["lastPostId"];
        $posts = get_posts([
            "post_type" => ["post", "page"],
            "posts_per_page" => 10, // to not overload the server
            "offset" => $lastPostId,
            "orderby" => "ID",
            "order" => "ASC",
            "lang" => "",
            "fields" => ["ID", "post_content"],
        ]);
        foreach ($posts as $post) {
            InternalsLinks::verifyPost($post);
            $lastPostId = $post->ID;
        }
        update_option(self::$OPTION_NAME_LAST_POST_ID, ["lastPostId" => $lastPostId, "lastExecuteTime" => time(), "lastCount" => count($posts)], false);
    }
}

$CronInitializeInternalLinks = new CronInitializeInternalLinks();
$CronInitializeInternalLinks->enableCron();
register_deactivation_hook(__FILE__, function () use ($CronInitializeInternalLinks) {
    $CronInitializeInternalLinks->disableCron();
});
$CronInitializeInternalLinks->init();

