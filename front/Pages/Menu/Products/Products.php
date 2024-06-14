<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Products;
use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

class Products {

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Explore all the tools"); ?></span>
        <span class="second-color"><?php echo TextDomain::__("SEO sans migraine 💊"); ?></span>
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
                <a class="product" href="<?php echo $product["link"]; ?>" target="_blank">
                    <div class="product-image">
                        <img src="<?php echo $product["image"]; ?>" alt="<?php echo $product["name"]; ?>">
                    </div>
                </a>
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
        <span><?php echo TextDomain::__("Each tool aim to help you in your SEO strategy and you can find them all here."); ?></span>
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