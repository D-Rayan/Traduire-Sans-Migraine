<?php

namespace TraduireSansMigraine\Front\Components;

use TraduireSansMigraine\Wordpress\TextDomain;

class Main {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Main.js", [], TSM__VERSION, true);
        wp_localize_script(TSM__SLUG . "-" . get_class(), "tsmI18N", [
            "Traduction en cours" => TextDomain::__("Traduction en cours"),
            "Traduction terminée" => TextDomain::__("Traduction terminée"),
            "La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site." => TextDomain::__("La traduction est en cours, vous pouvez fermer cette fenêtre et continuer à travailler sur votre site."),
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