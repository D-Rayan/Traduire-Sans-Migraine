<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Settings;

if (!defined("ABSPATH")) {
    exit;
}

class CronFixedInternalLinks
{
    private static $CRON_NAME = "traduire_sans_migraine_cron_fixed_table";

    public function __construct()
    {
    }

    public function init()
    {
        add_action(self::$CRON_NAME, [$this, "fixedUrls"]);
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


    public function fixedUrls()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["autoTranslateLinks"])) {
            return;
        }
    }
}

$CronFixedInternalLinks = new CronFixedInternalLinks();
$CronFixedInternalLinks->enableCron();
register_deactivation_hook(__FILE__, function () use ($CronFixedInternalLinks) {
    $CronFixedInternalLinks->disableCron();
});
$CronFixedInternalLinks->init();

