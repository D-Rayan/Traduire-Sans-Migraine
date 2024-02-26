<?php

namespace TraduireSansMigraine\Front\Pages\Editor;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\TextDomain;

include "OnSave/OnSave.php";
class Editor {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Editor.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsm", [
            "url" => admin_url("admin-ajax.php") . "?action=traduire-sans-migraine_",
            "postUrl" => admin_url("post.php"),
        ]);
    }

    public function loadAssetsAdmin() {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"]);
    }

    public function loadAssetsClient() {
        // nothing to load
    }
    public function loadAssets()
    {
        if (is_admin()) {
            $this->loadAssetsAdmin();
        } else {
            $this->loadAssetsClient();
        }
    }
    public function displayTraduireSansMigraineMetabox()
    {
        ?>
        <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
        <?php
            Button::render(TextDomain::__("Open my translate tab"), "primary", "display-traduire-sans-migraine-button");
        ?>
        </div>
        <?php
    }

    public function registerMetaBox() {
        add_meta_box( 'metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'post', 'side', 'high');
        add_meta_box( 'metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], 'page', 'side', 'high');
    }

    public function loadHooks() {

    }

    public function redirectAfterUpdating( $location ) {

        if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) ) {
            $tsmShow = isset($_POST["traduire-sans-migraine–is-enable"]) ? $_POST["traduire-sans-migraine–is-enable"] : "off";
            return $location . "&tsmShow=" . $tsmShow;
        }

        return $location;
    }

    public function addButton() {
        Button::render(TextDomain::__("Translate 💊"), "primary", "display-traduire-sans-migraine-button");
    }
    public function loadAdminHooks() {
        add_action( 'add_meta_boxes', [$this, 'registerMetaBox'] );
        add_action( 'add_meta_boxes_page', [$this, 'registerMetaBox'] );
        add_filter( 'redirect_post_location', [$this, "redirectAfterUpdating"] );
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }
}

$editor = new Editor();
$editor->init();