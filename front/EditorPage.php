<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\TextDomain;

class EditorPage extends Page
{
    private static $instance = null;
    private $allowedTypes;
    private $currentPostType;

    public static function init()
    {
        $instance = self::getInstance();
        $instance->loadAdminHooks();
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function loadAdminHooks()
    {
        global $pagenow;
        if (!is_admin()) {
            return;
        }
        $this->allowedTypes = apply_filters("tsm-post-type-translatable", ["post", "page", "elementor_library"]);
        $this->currentPostType = (isset($_GET["post"])) ? get_post_type($_GET["post"]) : null;
        $editingPostPageProduct = (isset($_GET["post"]) && $pagenow === "post.php" && in_array($this->currentPostType, $this->allowedTypes));
        $editingMail = (isset($_GET["page"]) && $_GET["page"] === "wc-settings" && isset($_GET["tab"]) && $_GET["tab"] === "email");
        if (!($editingPostPageProduct || $editingMail)) {
            return;
        }
        add_action('admin_enqueue_scripts', [$this, 'loadJSReact']);
        add_action('add_meta_boxes', [$this, 'registerMetaBox']);
        add_action('add_meta_boxes_page', [$this, 'registerMetaBox']);
        add_action('admin_footer', [$this, 'renderApp']);
    }

    public function renderApp()
    {
        ?>
        <div id="editor-app-traduire-sans-migraine"></div>
        <style>
            #editor-app-traduire-sans-migraine {
                display: none;
                position: fixed;
                top: 0;
                left: 0;
                width: 100vw;
                height: 100vh;
                background-color: rgba(0, 0, 0, 0.45);
                z-index: 90000;
            }

            .button#display-traduire-sans-migraine-button {
                background-color: #1626b0;
                border-color: #1626b0;
                padding: 0.2rem 1rem;
                border-radius: 20px;
                font-weight: 600;
                color: white;

                &:hover {
                    background-color: white;
                    color: #1626b0;
                    cursor: pointer;
                }
            }

            .components-button#display-traduire-sans-migraine-button:hover {
                text-decoration: underline;
            }
        </style>
        <?php
    }

    public function loadJSReact()
    {
        self::injectApplication('Editor');
    }

    public function displayTraduireSansMigraineMetabox()
    {
        if ($this->currentPostType === "product") {
            ?>
            <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
                <button class="components-button woocommerce-layout__activity-panel-tab"
                        id="display-traduire-sans-migraine-button"><span
                            style="height:24px;width:24px;">💊</span><br/> <?php echo TextDomain::__("Translate"); ?>
                </button>
            </div>
            <?php
        } else { ?>
            <div style="width: 100%; display: flex; flex-direction: column; justify-content: center; align-items: center; gap: 1rem;">
                <button class="button button-primary"
                        id="display-traduire-sans-migraine-button"><?php echo TextDomain::__("Translate 💊"); ?></button>
            </div>
            <?php
        }
    }

    public function registerMetaBox()
    {
        foreach ($this->allowedTypes as $type) {
            if ($type !== $this->currentPostType) {
                continue;
            }
            add_meta_box('metaboxTraduireSansMigraine', TSM__NAME, [$this, "displayTraduireSansMigraineMetabox"], $type, 'side', 'high');
        }
    }
}