<?php

namespace TraduireSansMigraine\Front\Components;

use TraduireSansMigraine\Wordpress\TextDomain;

class Main {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        $linkTraduireSansMigrainePractices = TextDomain::__("https://seosansmigraine.crisp.help/fr/article/bonnes-pratiques-de-traduction-seo-k3cmvy/");
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Main.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmI18N", [
            "Traduction en cours" => TextDomain::__("Translation in progress"),
            "Traduction terminée" => TextDomain::__("Translation is done"),
            "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site." => TextDomain::__("The translation is in progress. You can close this window if you want."),
            "successTranslationFirstShow" => TextDomain::__("You are on a post translated by Traduire Sans Migraine. If you want to know what are the best SEO practices you can click <a target='_blank' href='%s'>here</a>", $linkTraduireSansMigrainePractices),
            "successTranslationFirstShowTitle" => TextDomain::__("You may want to know this"),
        ]);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "style.css", [], TSM__VERSION);
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