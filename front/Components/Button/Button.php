<?php

namespace TraduireSansMigraine\Front\Components;

class Button {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-". get_class(), $this->path . "Button.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-". get_class(), $this->path . "Button.min.css", [], TSM__VERSION);
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

    public static function getHTML($content, $type, $id, $dataset = []) {
        ob_start();
        ?>
        <button
                data-default="<?php echo $content; ?>"
                id="<?php echo $id; ?>"
                class="traduire-sans-migraine-button traduire-sans-migraine-button--<?php echo $type; ?>"
            <?php
            foreach ($dataset as $key => $value) {
                echo "data-$key=\"$value\"";
            }
            ?>
        >
            <?php echo $content; ?>
        </button>
        <?php
        return ob_get_clean();
    }

    public static function render($content, $type, $id, $dataset = []) {
        echo self::getHTML($content, $type, $id, $dataset);
    }
}

$alert = new Button();
$alert->loadAssets();