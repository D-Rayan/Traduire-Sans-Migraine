<?php

namespace TraduireSansMigraine\Front\Components;

class Alert {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-". get_class(), $this->path . "Alert.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-". get_class(), $this->path . "Alert.css", [], TSM__VERSION);
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

    public static function getHTML($title, $message, $type, $options = [
            "isDismissible" => true
    ]) {
        ob_start();
        if ($title && !empty($title)) {
            ?>
            <div class="notice traduire-sans-migraine-alert traduire-sans-migraine-alert-<?php echo $type; ?> <?php echo $options["classname"]; ?>">
                <div class="traduire-sans-migraine-alert__title">
                    <span class="traduire-sans-migraine-alert__title-text"><?php echo $title; ?></span>
                    <?php echo ($options["isDismissible"] ? '<span class="traduire-sans-migraine-alert__title-close">X</span>' : '') ; ?>
                </div>
                <div class="traduire-sans-migraine-alert__body">
                    <?php echo $message; ?>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="notice traduire-sans-migraine-alert traduire-sans-migraine-alert-<?php echo $type; ?> <?php echo $options["classname"]; ?>">
                <div class="traduire-sans-migraine-alert__title">
                    <span></span>
                    <?php echo ($options["isDismissible"] ? '<span class="traduire-sans-migraine-alert__title-close">X</span>' : ''); ?>
                </div>
                <div>
                    <?php echo $message; ?>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    public static function render($title, $message, $type, $options = [
        "isDismissible" => true
    ]) {
        echo self::getHTML($title, $message, $type, $options);
    }
}

$alert = new Alert();
$alert->loadAssets();