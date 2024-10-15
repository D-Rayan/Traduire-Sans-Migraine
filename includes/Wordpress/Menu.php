<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Front\SettingsPage;

class Menu
{

    private static $instance = null;

    public static function init()
    {
        $instance = self::getInstance();
        add_action("admin_menu", [$instance, "displayMenu"]);
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function displayMenu()
    {
        if (!defined("SANS_MIGRAINE_MENU")) {
            $this->addCategorySansMigraine();
        }
        add_submenu_page(
            "traduire-sans-migraine",
            "⚙️ Traduire Sans Migraine",
            TextDomain::__("⚙️ Settings"),
            "manage_options",
            "traduire-sans-migraine",
            [$this, "renderSettingsPage"]
        );
        add_submenu_page(
            "traduire-sans-migraine",
            "⚙️ Traduire Sans Migraine",
            TextDomain::__("💊 Bulk Translation"),
            "manage_options",
            "traduire-sans-migraine#bulk",
            [$this, "renderSettingsPage"]
        );
        add_submenu_page(
            "traduire-sans-migraine",
            "Redirecting to the tutorial",
            TextDomain::__("See the tutorial"),
            "manage_options",
            "traduire-sans-migraine-tutorial",
            function () {
                $url = TextDomain::__("tutorialLink");
                ?>
                <script>
                    window.location.href = "<?php echo $url; ?>";
                </script>
                <?php
                exit;
            }
        );
    }

    private function addCategorySansMigraine()
    {
        $this->loadMenuIcon();
        add_menu_page(
            TextDomain::__("⚙️ Settings"),
            "Traduire sans migraine",
            "manage_options",
            "traduire-sans-migraine",
            [$this, "renderSettingsPage"],
            "dashicons-otter"
        );
        define("SANS_MIGRAINE_MENU", "sans-migraine");
    }

    public function loadMenuIcon()
    {
        add_action("admin_head", function () {
            echo '<style>
            .dashicons-otter::before {
                content: "💊";
                padding: 5px 0 !important;
            }
            #toplevel_page_traduire-sans-migraine li:last-child {
                font-weight: 600;
                padding-top: 5px;
                padding-bottom: 5px;
                background: #7795EE;
                color: white;
            }
            </style>';
        });
    }

    public function renderSettingsPage()
    {
        SettingsPage::render();
    }
}