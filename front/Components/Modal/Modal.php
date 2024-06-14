<?php

namespace TraduireSansMigraine\Front\Components;

class Modal {
    private $path;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Modal.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Modal.css", [], TSM__VERSION);
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

    public static function render($title, $message, $buttons = [], $options = []) {
        ?>
        <div class="traduire-sans-migraine-modal <?php if (isset($options["size"])) { echo 'traduire-sans-migraine-modal-size-' . $options["size"]; } ?>">
            <div class="traduire-sans-migraine-overlay"></div>
            <div class="traduire-sans-migraine-modal__content">
                <div class="traduire-sans-migraine-modal__content-header">
                    <div class="traduire-sans-migraine-modal__header-left">
                        <span class="traduire-sans-migraine-modal__content-header-title"><?php echo $title; ?></span>
                        <?php if (count($buttons) > 0) { ?>
                            <div class="traduire-sans-migraine-modal__content-header-buttons">
                                <?php
                                foreach ($buttons as $button) {
                                    echo $button;
                                }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                    <span class="traduire-sans-migraine-modal__content-header-close">X</span>
                </div>
                <div class="traduire-sans-migraine-modal__content-body">
                    <div class="traduire-sans-migraine-modal__content-body-text">
                        <?php echo $message; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}

$modal = new Modal();
$modal->loadAssets();