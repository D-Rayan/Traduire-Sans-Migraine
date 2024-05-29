<?php

namespace TraduireSansMigraine\Front\Pages\Editor;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Settings as SettingsPlugin;
use TraduireSansMigraine\Wordpress\TextDomain;

include "OnSave/OnSave.php";
class Editor {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        $settingsInstance = new SettingsPlugin();
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Editor.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmEditor", [
            "url" => admin_url("admin-ajax.php") . "?action=traduire-sans-migraine_",
            "postUrl" => admin_url("post.php"),
            "autoOpen" => $settingsInstance->settingIsEnabled("tsmOpenOnSave") ? "true" : "false",
            "_has_been_translated_by_tsm" => isset($_GET["post"]) ? get_post_meta($_GET["post"], "_has_been_translated_by_tsm", true) : false,
            "_tsm_first_visit_after_translation" => isset($_GET["post"]) ? get_post_meta($_GET["post"], "_tsm_first_visit_after_translation", true) : false,
        ]);
        if (isset($_GET["post"])) {
            delete_post_meta($_GET["post"], "_tsm_first_visit_after_translation");
        }
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
            Button::render(TextDomain::__("Translate 💊"), "primary", "display-traduire-sans-migraine-button");
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
        $settingsInstance = new SettingsPlugin();
        if ( isset( $_POST['save'] ) || isset( $_POST['publish'] ) && $settingsInstance->settingIsEnabled("tsmOpenOnSave") ) {
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