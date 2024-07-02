<?php

namespace TraduireSansMigraine\Front\Components;

use TraduireSansMigraine\Settings as SettingsPlugin;
use TraduireSansMigraine\Wordpress\TextDomain;

class Main {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        $settingsInstance = new SettingsPlugin();
        $linkTraduireSansMigrainePractices = TextDomain::__("https://www.seo-sans-migraine.fr/astuces-traduction-seo");
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Main.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmI18N", [
            "Traduction en cours" => TextDomain::__("Translation in progress"),
            "Traduction terminée" => TextDomain::__("Translation is done"),
            "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site." => TextDomain::__("The translation is in progress. You can close this window if you want."),
            "successTranslationFirstShow" => TextDomain::__("You are on a post translated by Traduire Sans Migraine. If you want to know what are the best SEO practices you can click <a target='_blank' href='%s'>here</a>", $linkTraduireSansMigrainePractices),
            "successTranslationFirstShowTitle" => TextDomain::__("You may want to know this"),
            "postTranslatedByTSMTitle" => TextDomain::__("Translated by Traduire Sans Migraine"),
            "postTranslatedByTSMMessage" => TextDomain::__("You're on a content translated by us. If you want to know the best SEO practices, click <a target='_blank' href='%s'>here</a>. Futhermore you can use the differents tools :", $linkTraduireSansMigrainePractices),
            "translateInternalLinksButton" => TextDomain::__("Traduire les liens internes"),
        ]);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmVariables", [
            "assetsURI" => TSM__ASSETS_PATH,
            "url" => admin_url("admin-ajax.php") . "?action=traduire-sans-migraine_",
            "postUrl" => admin_url("post.php"),
            "trashed" => isset($_GET["trashed"]) && isset($_GET["ids"]) && count(explode(",", $_GET["ids"])) === 1,
            "ids" => isset($_GET["ids"]) ? $_GET["ids"] : false,
            "autoOpen" => $settingsInstance->settingIsEnabled("tsmOpenOnSave") ? "true" : "false",
            "_has_been_translated_by_tsm" => isset($_GET["post"]) ? get_post_meta($_GET["post"], "_has_been_translated_by_tsm", true) : false,
            "_tsm_first_visit_after_translation" => isset($_GET["post"]) ? get_post_meta($_GET["post"], "_tsm_first_visit_after_translation", true) : false,
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

