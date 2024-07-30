<?php

namespace TraduireSansMigraine\Front\Components;

use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Settings as SettingsPlugin;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\TextDomain;

class Main {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        $settingsInstance = new SettingsPlugin();
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Main.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmI18N", [
            "Traduction en cours" => TextDomain::__("Translation in progress"),
            "Traduction terminée" => TextDomain::__("Translation is done"),
            "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site." => TextDomain::__("The translation is in progress. You can close this window if you want."),
        ]);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmVariables", [
            "assetsURI" => TSM__ASSETS_PATH,
            "url" => admin_url("admin-ajax.php") . "?action=traduire-sans-migraine_",
            "postUrl" => admin_url("post.php"),
            "trashed" => isset($_GET["trashed"]) && isset($_GET["ids"]) && count(explode(",", $_GET["ids"])) === 1,
            "ids" => isset($_GET["ids"]) ? $_GET["ids"] : false,
            "autoOpen" => $settingsInstance->settingIsEnabled("tsmOpenOnSave") ? "true" : "false",
            "wpNonce_editor_get_post_notifications" => isset($_GET["post"]) ? wp_create_nonce("traduire-sans-migraine_editor_get_post_notifications") : false,
            "wpNonce_article_deleted_render" => isset($_GET["ids"]) && count(explode(",", $_GET["ids"])) === 1 ? wp_create_nonce("traduire-sans-migraine_article_deleted_render") : false
        ]);
        if (isset($_GET["post"])) {
            delete_post_meta($_GET["post"], "_tsm_first_visit_after_translation");
        }
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "style.min.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"]);
        add_action("admin_enqueue_scripts", [$this, "enqueueStyles"]);
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
}

$modal = new Main();
$modal->loadAssets();

