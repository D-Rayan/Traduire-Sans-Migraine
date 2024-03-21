<?php

namespace TraduireSansMigraine\Front\Pages\Menu;
use TraduireSansMigraine\Front\Components\Button;use TraduireSansMigraine\Wordpress\TextDomain;

class Menu {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Menu.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Menu.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        if (!isset($_GET["page"]) || !strstr($_GET["page"], "sans-migraine")) {
            return;
        }
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

    public function loadHooks() {

    }

    public function loadAdminHooks() {
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    static function render($title, $description, $content, $picture = "loutre_amandine.png") {
        ?>
        <div class="wrap">
            <div class="header">
                <div class="menu">
                    <div class="logo">
                        <a href="https://www.seo-sans-migraine.fr/">
                            <img src="<?php echo TSM__ASSETS_PATH; ?>seo_sans_migraine_logo.png" />
                        </a>
                    </div>
                    <div class="content">
                        <span><?php echo TextDomain::__("Happy with our products? Help us with few words. ⭐️⭐️⭐️⭐️⭐️"); ?></span>
                    </div>
                    <div class="cta">
                        <?php
                        Button::render("Contactez-moi", "primary", "contact-me");
                        Button::render("Voir mon compte", "primary", "my-account", [
                            "href" => TSM__CLIENT_LOGIN_DOMAIN
                        ]);
                        ?>
                    </div>
                </div>
                <div class="header-content">
                    <div class="top">
                        <div class="title">
                            <?php echo $title; ?>
                        </div>
                        <div class="description">
                            <?php echo $description; ?>
                        </div>
                    </div>
                    <div class="right">
                        <div>
                            <div>
                                <img src="<?php echo TSM__ASSETS_PATH; ?><?php echo $picture; ?>" />
                            </div>
                        </div>
                    </div>
                </div>
            </div>
            <div class="transition-dechirure"></div>
            <div class="content">
                <?php echo $content; ?>
            </div>
        </div>
        <?php
    }
}

$menu = new Menu();
$menu->init();