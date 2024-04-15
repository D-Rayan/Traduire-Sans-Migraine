<?php

namespace TraduireSansMigraine\Front\Components;

class Checkbox {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        // nothing to load
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Checkbox.css", [], TSM__VERSION);
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

    public static function getHTML($label, $id, $checked = false, $disabled = false) {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-checkbox">
            <input <?php echo $disabled ? "disabled" : ""; ?> type="checkbox" class="substituted" aria-hidden="true" id="<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $checked ? "checked" : ""; ?>>
            <label for="<?php echo $id; ?>"><?php echo $label; ?></label>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render($label, $id, $checked = false, $disabled = false) {
        echo self::getHTML($label, $id, $checked, $disabled);
    }
}

$modal = new Checkbox();
$modal->loadAssets();