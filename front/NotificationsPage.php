<?php

namespace TraduireSansMigraine\Front;

class NotificationsPage extends Page
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
        self::injectApplication('Notifications');
    }
}