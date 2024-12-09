<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;
use TraduireSansMigraine\Wordpress\Object\InternalsLinks;

if (!defined("ABSPATH")) {
    exit;
}

class CronInitializeInternalLinks
{
    private static $OPTION_NAME_LAST_POST_ID = "tsm-internal-links-cron-state";
    private static $CRON_NAME = "traduire_sans_migraine_cron_initialize_table";

    public function __construct() {}

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
        $options = self::getOption();
        $hasAlreadyBeenInitialized = isset($options["hasBeenInitialized"]) && $options["hasBeenInitialized"];
        add_filter('cron_schedules', function ($schedules) use ($hasAlreadyBeenInitialized) {
            if ($hasAlreadyBeenInitialized) {
                $schedules['every_minute_initialize'] = array(
                    'interval' => 60,
                    'display' => __('Every minute')
                );
            } else {
                $schedules['every_10_seconds_initialize'] = array(
                    'interval' => 10,
                    'display' => __('Every 10 seconds')
                );
            }

            return $schedules;
        });
        if (wp_next_scheduled(self::$CRON_NAME) === false) {
            wp_schedule_event(time(), $hasAlreadyBeenInitialized ? 'every_minute_initialize' : 'every_10_seconds_initialize', self::$CRON_NAME);
        }
    }

    public static function reset()
    {
        update_option(self::$OPTION_NAME_LAST_POST_ID, ["hasBeenInitialized" => false, "delay" => 0, "lastPostId" => 0, "lastExecuteTime" => 0, "lastCount" => 0], false);
        InternalsLinks::reset();
    }

    public static function getOption()
    {
        return get_option(self::$OPTION_NAME_LAST_POST_ID, ["hasBeenInitialized" => false, "delay" => 0, "lastPostId" => 0, "lastExecuteTime" => 0, "lastCount" => 0]);
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

    public function initializeTable()
    {
        $start = microtime(true);
        $stateCron = self::getOption();
        $lastPostId = $stateCron["lastPostId"];
        $posts = DAOInternalsLinks::getPostsCron($lastPostId);
        foreach ($posts as $post) {
            InternalsLinks::verifyPost($post);
            $lastPostId = $post->ID;
        }
        $lastCount = count($posts);
        $hasBeenInitialized = (isset($stateCron["hasBeenInitialized"]) && $stateCron["hasBeenInitialized"]) || $lastCount < 10;
        $delay = microtime(true) - $start;
        update_option(self::$OPTION_NAME_LAST_POST_ID, [
            "delay" => $delay,
            "lastPostId" => $lastPostId,
            "lastExecuteTime" => time(),
            "lastCount" => $lastCount,
            "hasBeenInitialized" => $hasBeenInitialized
        ], false);
    }
}

$CronInitializeInternalLinks = new CronInitializeInternalLinks();
$CronInitializeInternalLinks->enableCron();
register_deactivation_hook(__FILE__, function () use ($CronInitializeInternalLinks) {
    $CronInitializeInternalLinks->disableCron();
});
$CronInitializeInternalLinks->init();
