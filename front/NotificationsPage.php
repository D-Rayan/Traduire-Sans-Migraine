<?php

namespace TraduireSansMigraine\Front;

class NotificationsPage
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
        if (!isset($_GET["post"]) || $pagenow !== "post.php" || !in_array(get_post_type($_GET["post"]), ["post", "page"]) || !is_admin()) {
            return;
        }
        add_action('admin_enqueue_scripts', [$this, 'loadJSReact']);
        add_action('admin_footer', [$this, 'renderApp']);
    }

    public function renderApp()
    {
        ?>
        <div id="notifications-app-traduire-sans-migraine"></div>
        <?php
    }

    public function loadJSReact()
    {
        global $tsm;

        $asset_file = plugin_dir_path(__FILE__) . 'build/Notifications/index.tsx.asset.php';
        if (!file_exists($asset_file)) {
            return;
        }
        $asset = include $asset_file;

        wp_enqueue_script('notifications-page-app', plugins_url('build/Notifications/index.tsx.js', __FILE__), $asset['dependencies'], $asset['version'], ['in_footer' => true]);
        $assetsCss = plugin_dir_url(__FILE__) . 'build/Notifications/index.tsx.css';
        wp_enqueue_style('notifications-page-app', $assetsCss, [], $asset['version']);
        wp_localize_script('notifications-page-app', 'traduireSansMigraineVariables', [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('traduire-sans-migraine'),
            'token' => $tsm->getSettings()->getToken(),
            'translations' => $tsm->getPolylangManager()->getAllTranslationsPost($_GET["post"]),
            'firstVisitAfterTSMTranslatedIt' => get_post_meta($_GET["post"], '_tsm_first_visit_after_translation', true),
            'hasTSMTranslatedIt' => get_post_meta($_GET["post"], '_has_been_translated_by_tsm', true),
            'translatedFromSlug' => get_post_meta($_GET["post"], '_translated_by_tsm_from', true),
            'currentLocale' => get_locale(),
            'languages' => $tsm->getPolylangManager()->getLanguages(),
            'postId' => $_GET["post"],
            "polylangUrl" => defined("POLYLANG_FILE") ? plugin_dir_url(POLYLANG_FILE) : "",
            'urlClient' => TSM__CLIENT_LOGIN_DOMAIN,
        ]);
        delete_post_meta($_GET["post"], '_tsm_first_visit_after_translation');
    }
}