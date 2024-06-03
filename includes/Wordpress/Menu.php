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
            </style>';
        });
    }

    public function addMenu() {
        if (defined("SANS_MIGRAINE_MENU")) {
            return;
        }
        $this->loadMenuIcon();
        add_menu_page(
            "Nos Produits",
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
                menuSanMigraine.innerHTML = "Nos produits";
            </script>
            <?php
        });
    }

    public function addSubMenu() {
        add_submenu_page(
            "sans-migraine",
            "⚙️ Traduire Sans Migraine",
            "⚙️ Paramètres",
            "manage_options",
            "traduire-sans-migraine",
            [$this, "renderSubMenu"]
        );
        add_submenu_page(
            "sans-migraine",
            "⚙️ Traduire Sans Migraine",
            "x️ Traduire",
            "manage_options",
            "traduire-sans-migraine-bulk",
            [$this, "renderBulkMenu"]
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