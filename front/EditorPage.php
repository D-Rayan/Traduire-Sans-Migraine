<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class EditorPage
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
        global $tsm;

        $asset_file = plugin_dir_path(__FILE__) . 'build/Editor/index.tsx.asset.php';
        if (!file_exists($asset_file)) {
            return;
        }
        $asset = include $asset_file;

        wp_enqueue_script('editor-page-app', plugins_url('build/Editor/index.tsx.js', __FILE__), $asset['dependencies'], $asset['version'], ['in_footer' => true]);
        $assetsCss = plugin_dir_url(__FILE__) . 'build/Editor/index.tsx.css';
        wp_enqueue_style('editor-page-app', $assetsCss, [], $asset['version']);
        wp_localize_script('editor-page-app', 'traduireSansMigraineVariables', [
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