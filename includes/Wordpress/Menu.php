<?php

namespace TraduireSansMigraine\Wordpress;

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
            "Sans Migraine",
            "manage_options",
            "sans-migraine",
            [$this, "renderMenu"],
            "dashicons-otter"
        );
        define("SANS_MIGRAINE_MENU", "sans-migraine");
    }

    public function addSubMenu() {
        add_submenu_page(
            "sans-migraine",
            "Traduire Sans Migraine",
            "Traduire Sans Migraine",
            "manage_options",
            "traduire-sans-migraine",
            [$this, "renderSubMenu"]
        );
    }

    public function renderMenu() {
        Products::render();
    }

    public function renderSubMenu() {
        Settings::render();
    }
}