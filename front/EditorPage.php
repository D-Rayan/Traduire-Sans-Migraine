<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class EditorPage extends Page
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
        add_action('add_meta_boxes', [$this, 'registerMetaBox']);
        add_action('add_meta_boxes_page', [$this, 'registerMetaBox']);
        add_action('admin_footer', [$this, 'renderApp']);
    }

    public function renderApp()
    {
        ?>
        <div id="editor-app-traduire-sans-migraine"></div>
        <style>
            #editor-app-traduire-sans-migraine {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.45);
                z-index: 90000;
            }

            #display-traduire-sans-migraine-button {
                background-color: #1626b0;
                border-color: #1626b0;
                padding: 0.2rem 1rem;
                border-radius: 20px;
                font-weight: 600;

                &:hover {
                    background-color: white;
                    color: #1626b0;
                    cursor: pointer;
                }
            }
        </style>
        <?php
    }

    public function loadJSReact()
    {
        self::injectApplication('Editor');
    }

    public function displayTraduireSansMigraineMetabox()
    {
        ?>
        <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
            <button class="button button-primary"
                    id="display-traduire-sans-migraine-button"><?php echo TextDomain::__("Translate 💊"); ?></button>
        </div>
        <?php
    }

    public function registerMetaBox()
    {
        add_meta_box('metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'post', 'side', 'high');
        add_meta_box('metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'page', 'side', 'high');
    }
}