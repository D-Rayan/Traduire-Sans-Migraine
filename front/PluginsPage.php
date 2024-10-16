<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class PluginsPage
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
        global $tsm;
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
        $asset_file = plugin_dir_path(__FILE__) . 'build/Plugins/index.tsx.asset.php';
        if (!file_exists($asset_file)) {
            return;
        }
        $asset = include $asset_file;

        wp_enqueue_script('plugins-page-app', plugins_url('build/Plugins/index.tsx.js', __FILE__), $asset['dependencies'], $asset['version'], ['in_footer' => true]);
        $assetsCss = plugin_dir_url(__FILE__) . 'build/Plugins/index.tsx.css';
        wp_enqueue_style('plugins-page-app', $assetsCss, [], $asset['version']);
        wp_localize_script('plugins-page-app', 'traduireSansMigraineVariables', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('traduire-sans-migraine'),
            'token' => $tsm->getSettings()->getToken(),
            'currentLocale' => get_locale(),
            'languages' => $tsm->getPolylangManager()->getLanguages(),
            "polylangUrl" => defined("POLYLANG_FILE") ? plugin_dir_url(POLYLANG_FILE) : "",
            'urlClient' => TSM__CLIENT_LOGIN_DOMAIN,
        ]);
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