<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Products;
use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

class Products {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Products.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Products.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        if (!isset($_GET["page"]) || $_GET["page"] !== "sans-migraine") {
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

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Our products"); ?></span><br/>
        <span class="second-color"><?php echo TextDomain::__("Sans-Migraine"); ?></span>
        <?php
        return ob_get_clean();
    }

    private static function getContent() {
        ob_start();
        $client = new Client();
        $products = $client->getProducts();
        ?>
        <div class="products">
            <?php
            foreach ($products as $product) {
                ?>
                <div class="product">
                    <div class="product-image">
                        <img src="<?php echo $product["image"]; ?>" alt="seo-sans-migraine">
                    </div>
                    <div class="product-content">
                        <h2><?php echo $product["name"]; ?></h2>
                        <p><?php echo $product["description"]; ?></p>
                    </div>
                </div>
                <?php
            }
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function getDescription() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Products designed to help you in your SEO, without any headache"); ?></span>
        <?php
        return ob_get_clean();
    }
    static function render() {
        $content = self::getContent();
        $title = self::getTitle();
        $description = self::getDescription();
        Menu::render($title, $description, $content);
    }
}

$products = new Products();
$products->init();