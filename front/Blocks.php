<?php

namespace TraduireSansMigraine\Front;

use Automattic\WooCommerce\Admin\Features\ProductBlockEditor\BlockRegistry;
use TraduireSansMigraine\Wordpress\TextDomain;

class Blocks extends Page
{
    private static $instance = null;

    public static function init()
    {
        self::init_blocks();
        $instance = self::getInstance();
        $instance->loadAdminHooks();
    }

    private static function init_blocks()
    {
        // get all folder in Blocks directory
        $blocks = scandir(__DIR__ . '/build/Blocks');
        foreach ($blocks as $block) {
            if ($block === '.' || $block === '..') {
                continue;
            }
            add_action("init", function () use ($block) {
                BlockRegistry::get_instance()->register_block_type_from_metadata(__DIR__ . '/build/Blocks/' . $block);
            });
        }
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
        if ($pagenow !== "admin.php" || !isset($_GET["page"]) || $_GET["page"] !== "wc-admin" || !isset($_GET["path"]) || !strstr($_GET["path"], "product")) {
            return;
        }
        add_action('admin_enqueue_scripts', [$this, 'loadJSReact']);
        add_action('admin_footer', [$this, 'renderApp']);
    }

    public function loadJSReact()
    {
        self::injectApplication('Editor');
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

            .button#display-traduire-sans-migraine-button {
                background-color: #1626b0;
                border-color: #1626b0;
                padding: 0.2rem 1rem;
                border-radius: 20px;
                font-weight: 600;
                color: white;

                &:hover {
                    background-color: white;
                    color: #1626b0;
                    cursor: pointer;
                }
            }
        </style>
        <button class="button"
                id="display-traduire-sans-migraine-button"><?php echo TextDomain::__("Translate 💊"); ?>
        </button>
        <?php
    }
}