<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class EditorPage {
    public function renderApp() {
        ?>
        <div id="editor-app-traduire-sans-migraine" style="width: 100vw;
            height: 100vh;
            z-index: 99999;
            position: fixed;
            left: 0;
            top: 0;
            background: rgba(255, 255, 255, 0.8);
            backdrop-filter: blur(3px);display:none;"></div>
        <?php
    }

    public function loadJSReact() {
        $asset_file = plugin_dir_path( __FILE__ ) . 'build/Editor/index.tsx.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;
        wp_enqueue_script( 'editor-page-app', plugins_url( 'build/Editor/index.tsx.js', __FILE__ ), $asset['dependencies'], $asset['version'], ['in_footer' => true]);
    }

    public function displayTraduireSansMigraineMetabox()
    {
        ?>
        <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
            <button class="button button-primary" id="display-traduire-sans-migraine-button"><?php echo TextDomain::__("Translate 💊"); ?></button>
        </div>
        <?php
    }

    public function registerMetaBox() {
        add_meta_box( 'metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'post', 'side', 'high');
        add_meta_box( 'metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'page', 'side', 'high');
    }

    public function loadAdminHooks() {
        global $pagenow;
        if (!isset($_GET["post"]) || $pagenow !== "post.php" || !in_array(get_post_type($_GET["post"]), ["post", "page"]) || !is_admin()) {
            return;
        }
        add_action('admin_enqueue_scripts', [$this, 'loadJSReact']);
        add_action( 'add_meta_boxes', [$this, 'registerMetaBox'] );
        add_action( 'add_meta_boxes_page', [$this, 'registerMetaBox'] );
        add_action('admin_footer', [$this, 'renderApp']);
    }

    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function init() {
        $instance = self::getInstance();
        $instance->loadAdminHooks();
    }
}