<?php


namespace TraduireSansMigraine\Wordpress\Shortcodes;


if (!defined("ABSPATH")) {
    exit;
}

class MenuLanguage
{

    private $list;
    private $name;
    private $flag;
    private $hideEmpty;
    private $redirectHome;
    private $hideCurrent;

    public function __construct()
    {
    }

    public function registerCss()
    {
        wp_register_style("menu-language", TSM__FRONT_PATH . "css/MenuLanguage.min.css");
        wp_enqueue_style("menu-language");
    }

    public function init()
    {
        -
        add_action("wp_enqueue_scripts", [$this, "registerCss"]);
        add_shortcode("menu_language", [$this, "displayMenuLanguage"]);
    }

    public function displayMenuLanguage($args)
    {
        global $tsm;
        $id = get_the_ID();
        $Polylang = $tsm->getPolylangManager();
        $this->setArguments($args);
        $languages = $id === false ? $Polylang->getLanguagesActives() : $Polylang->getAllTranslationsPost($id);
        $currentLanguageSlug = $Polylang->getCurrentLanguageSlug();
        ob_start();
        $this->displayContainerStart();
        $position = 0;
        usort($languages, function ($a, $b) {
            if ($a["default"]) {
                return -1;
            }
            if ($b["default"]) {
                return -1;
            }
            return strcmp($a["name"], $b["name"]);
        });
        foreach ($languages as $language) {
            if ($this->hideEmpty && $language["no_translation"]) {
                continue;
            }
            if ($this->hideCurrent && $currentLanguageSlug === $language["code"]) {
                continue;
            }
            $this->displayLanguage($language, $position++);
        }
        $this->displayContainerEnd();
        return ob_get_clean();
    }

    private function setArguments($args)
    {
        $properties = shortcode_atts(
            [
                "list" => "false",
                "name" => "false",
                "flag" => "false",
                "hideEmpty" => "false",
                "redirectHome" => "false",
                "hideCurrent" => "false",
            ], $args, "menu_language"
        );
        $this->list = boolval($properties["list"]);
        $this->name = boolval($properties["name"]);
        $this->flag = boolval($properties["flag"]);
        $this->hideEmpty = boolval($properties["hideEmpty"]);
        $this->redirectHome = boolval($properties["redirectHome"]);
        $this->hideCurrent = boolval($properties["hideCurrent"]);
    }

    private function displayContainerStart()
    {
        $classList = ["menu-language"];
        if ($this->list) {
            $classList[] = "list";
        }
        echo "<div class='" . implode(" ", $classList) . "'>";
    }

    private function displayLanguage($language, $position)
    {
        if (!$this->redirectHome && empty($language["postId"])) {
            return;
        }
        global $tsm;
        $Polylang = $tsm->getPolylangManager();
        $url = $this->redirectHome ? $Polylang->getHomeUrl($language["code"]) : get_permalink($language["postId"]);
        echo '<a href="' . $url . '">';
        if ($this->flag) {
            echo $language["flag"];
        }
        if ($this->name) {
            echo $language["name"];
        } else {
            echo $language["code"];
        }
        if ($position === 0) {
            ?>
            <svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em">
                <path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path>
            </svg>
            <?php
        }
        echo "</a>";
    }

    private function displayContainerEnd()
    {
        echo "</div>";
    }
}

$MenuLanguage = new MenuLanguage();
$MenuLanguage->init();