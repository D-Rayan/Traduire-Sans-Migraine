<?php

namespace TraduireSansMigraine\Wordpress;

use TraduireSansMigraine\Front\Pages\Menu\Bulk\Bulk;
use TraduireSansMigraine\Front\Pages\Menu\Products\Products;
use TraduireSansMigraine\Front\Pages\Menu\Settings\Settings;

class Menu {

    public function init() {
        add_action("admin_menu", [$this, "addMenu"]);
        add_action("admin_menu", [$this, "addSubMenu"]);
    }

    public function loadMenuIcon() {
        add_action("admin_head", function() {
            echo '<style>
            .dashicons-otter::before {
                content: "💊";
                padding: 5px 0 !important;
            }
            #toplevel_page_sans-migraine li:last-child {
                font-weight: 600;
                padding-top: 5px;
                padding-bottom: 5px;
                background: #7795EE;
                color: white;
            }
            </style>';
        });
    }

    public function addMenu() {
        if (defined("SANS_MIGRAINE_MENU")) {
            return;
        }
        $this->loadMenuIcon();
        add_menu_page(
            TextDomain::__("Our Products"),
            "Traduire sans migraine",
            "manage_options",
            "sans-migraine",
            [$this, "renderMenu"],
            "dashicons-otter"
        );
        define("SANS_MIGRAINE_MENU", "sans-migraine");
        $this->rewriteTextMenu();
    }

    public function rewriteTextMenu() {
        add_action("admin_footer", function() {
            ?>
            <script style="text/javascript">
                const menuSanMigraine = document.querySelector("a[href*='page=sans-migraine'].wp-first-item");
                menuSanMigraine.innerHTML = "<?php echo TextDomain::__("Our Products"); ?>";
            </script>
            <?php
        });
    }

    public function addSubMenu() {
        add_submenu_page(
            "sans-migraine",
            "⚙️ Traduire Sans Migraine",
            TextDomain::__("⚙️ Settings"),
            "manage_options",
            "traduire-sans-migraine",
            [$this, "renderSubMenu"]
        );
        add_submenu_page(
            "sans-migraine",
            "⚙️ Traduire Sans Migraine",
            TextDomain::__("💊 Bulk Translation"),
            "manage_options",
            "traduire-sans-migraine-bulk",
            [$this, "renderBulkMenu"]
        );
        add_submenu_page(
            "sans-migraine",
            "Redirecting to the tutorial",
            TextDomain::__("See the tutorial"),
            "manage_options",
            "traduire-sans-migraine-bulk",
            function () {
                wp_redirect(TextDomain::__("tutorialLink"));
                exit;
            }
        );
    }

    public function renderMenu() {
        Products::render();
    }

    public function renderSubMenu() {
        Settings::render();
    }

    public function renderBulkMenu() {
        Bulk::render();
    }
}