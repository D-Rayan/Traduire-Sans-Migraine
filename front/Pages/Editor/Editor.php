<?php

namespace TraduireSansMigraine\Front\Pages\Editor;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Settings as SettingsPlugin;
use TraduireSansMigraine\Wordpress\TextDomain;

include "OnSave/OnSave.php";
class Editor {

    public function __construct() {
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
        $this->loadAdminHooks();
    }
}

$editor = new Editor();
$editor->init();