<?php

namespace TraduireSansMigraine\Front\Pages\Plugins;
use TraduireSansMigraine\Front\Components\Button;use TraduireSansMigraine\Wordpress\TextDomain;

include "ReasonsDeactivate/ReasonsDeactivate.php";

class Plugins {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Plugins.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Plugins.min.css", [], TSM__VERSION);
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

    public function loadHooks() {

    }

    public function loadAdminHooks() {
        add_filter( 'plugin_action_links', [$this, "addPluginActionLinks"], 10, 2 );
    }

    public function addPluginActionLinks( $links, $file )
    {
        if ( $file == TSM__PLUGIN_BASENAME )
        {
            if (isset($links["deactivate"])) {
                $links["deactivate"] = str_replace("<a", "<a data-wpnonce='" . wp_create_nonce("traduire-sans-migraine_plugins_reasons_deactivate") . "'", $links["deactivate"]);
            }
        }
        return $links;
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }
}

$plugins = new Plugins();
$plugins->init();