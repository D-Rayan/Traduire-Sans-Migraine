<?php

namespace TraduireSansMigraine\Front\Components;

class Suggestions {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-". get_class(), $this->path . "Suggestions.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-". get_class(), $this->path . "Suggestions.min.css", [], TSM__VERSION);
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

    public static function getHTML($title, $message, $footer, $options = []) {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-suggestion <?php if (isset($options["classname"])) { echo $options["classname"]; } ?>">
            <div class="traduire-sans-migraine-suggestion-title"><?php echo $title; ?></div>
            <div class="traduire-sans-migraine-suggestion-content"><?php echo $message; ?></div>
            <div class="traduire-sans-migraine-suggestion-footer"><?php echo $footer; ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render($title, $message, $footer, $options = []) {
        echo self::getHTML($title, $message, $footer, $options);
    }
}

$Suggestions = new Suggestions();
$Suggestions->loadAssets();