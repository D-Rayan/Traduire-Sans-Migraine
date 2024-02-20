<?php

namespace TraduireSansMigraine\Front\Components;

class Tooltip {
    private $path;
    static $javascriptInjected = false;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-". get_class(), $this->path . "Tooltip.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-". get_class(), $this->path . "Tooltip.css", [], TSM__VERSION);
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

    public static function getHTML($innerHTML, $tooltipMessage) {
        ob_start();
        ?>
        <span class="traduire-sans-migraine-tooltip">
            <div class="traduire-sans-migraine-tooltip-content">
                <?php echo $tooltipMessage; ?>
            </div>
            <span class="traduire-sans-migraine-tooltip-hoverable"><?php echo $innerHTML; ?></span>
        </span>
        <?php
        return ob_get_clean();
    }

    public static function render($innerHTML, $tooltipMessage) {
        echo self::getHTML($innerHTML, $tooltipMessage);
    }
}

$Tooltip = new Tooltip();
$Tooltip->loadAssets();