<?php

namespace TraduireSansMigraine\Front\Components;

class Main {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        // nothing to load
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