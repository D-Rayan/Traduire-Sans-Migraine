<?php

namespace TraduireSansMigraine\Wordpress\Hooks;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOInternalsLinks;
use TraduireSansMigraine\Wordpress\Object\InternalsLinks;

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
            $schedules['every_minute_fixed'] = array(
                'interval' => 60,
                'display' => __('Every minute')
            );

            return $schedules;
        });
        if (wp_next_scheduled(self::$CRON_NAME) === false) {
            wp_schedule_event(time(), 'every_minute_fixed', self::$CRON_NAME);
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
        $internalsLinksData = DAOInternalsLinks::getFixable();
        foreach ($internalsLinksData as $internalLinkData) {
            $internalLink = new InternalsLinks($internalLinkData);
            $internalLink
                ->fix()
                ->save();
        }
    }
}

$CronFixedInternalLinks = new CronFixedInternalLinks();
$CronFixedInternalLinks->enableCron();
register_deactivation_hook(__FILE__, function () use ($CronFixedInternalLinks) {
    $CronFixedInternalLinks->disableCron();
});
$CronFixedInternalLinks->init();
