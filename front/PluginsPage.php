<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class PluginsPage extends Page
{
    private static $instance = null;

    public static function init()
    {
        $instance = self::getInstance();
        $instance->loadAdminHooks();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function loadAdminHooks()
    {
        global $pagenow;
        if ($pagenow !== "plugins.php" || !is_admin()) {
            return;
        }
        add_action('admin_footer', [$this, 'renderApp']);
        add_filter('plugin_action_links', [$this, "addPluginActionLinks"], 10, 2);
    }

    public function renderApp()
    {
        ?>
        <div id="plugins-app-traduire-sans-migraine"></div>
        <style>
            .mark-by {
                background: #3B99FC;
                font-weight: 500;
                color: white;
                padding: 0.2rem 0.5rem;
            }
        </style>
        <?php
        self::injectApplication('Plugins');
    }

    public function addPluginActionLinks($links, $file)
    {
        if ($file == TSM__PLUGIN_BASENAME) {
            $links["settings"] = '<a href="' . admin_url('admin.php?page=traduire-sans-migraine') . '">' . TextDomain::__("Settings") . '</a>';
            array_unshift($links, '<a href="https://www.seo-sans-migraine.fr/">' . TextDomain::__("By ") . '<span class="mark-by">Otter Corp</span></a>');
        }
        return $links;
    }
}