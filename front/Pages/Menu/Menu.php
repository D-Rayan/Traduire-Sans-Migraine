<?php

namespace TraduireSansMigraine\Front\Pages\Menu;
use TraduireSansMigraine\Front\Components\Button;use TraduireSansMigraine\Wordpress\TextDomain;

class Menu {

    static function render($title, $description, $content, $picture = "") {
        ?>
        <div class="wrap">
            <div class="header">
                <div class="menu">
                    <div class="logo">
                        <a href="https://www.seo-sans-migraine.fr/" target="_blank">
                            <img src="<?php echo TSM__ASSETS_PATH; ?>seo_sans_migraine_logo.png" alt="logo_seo_sans_migraine" />
                        </a>
                    </div>
                    <div class="content">
                        <a class="no-decoration" href="https://fr.trustpilot.com/review/seo-sans-migraine.fr" target="_blank"><?php echo TextDomain::__("Happy with our products? Help us with few words. ⭐️⭐️⭐️⭐️⭐️"); ?></a>
                    </div>
                    <div class="cta">
                        <?php
                        Button::render("Contactez-moi", "primary", "contact-me", [
                            "href" => TSM__CLIENT_LOGIN_DOMAIN . "?contact=1"
                        ]);
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
                    <?php if (!empty($picture)) { ?>
                        <div class="right">
                            <div>
                                <div>
                                    <img src="<?php echo TSM__ASSETS_PATH; ?><?php echo $picture; ?>" />
                                </div>
                            </div>
                        </div>
                    <?php } ?>
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