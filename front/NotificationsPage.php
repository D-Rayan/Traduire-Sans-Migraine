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
        if (!is_admin()) {
            return;
        }
        $allowedTypes = apply_filters("tsm-post-type-translatable", ["post", "page", "elementor_library"]);
        $editingPostPageProduct = (isset($_GET["post"]) && $pagenow === "post.php" && in_array(get_post_type($_GET["post"]), $allowedTypes));
        $editingMail = (isset($_GET["page"]) && $_GET["page"] === "wc-settings" && isset($_GET["tab"]) && $_GET["tab"] === "email");
        if (!($editingPostPageProduct || $editingMail)) {
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