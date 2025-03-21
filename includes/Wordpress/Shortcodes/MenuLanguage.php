<?php


namespace TraduireSansMigraine\Wordpress\Shortcodes;


use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\TextDomain;

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

    /**
     * @var TranslationPost $translations
     */
    private $translations;

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
        add_action("wp_enqueue_scripts", [$this, "registerCss"]);
        add_shortcode("menu_language", [$this, "displayMenuLanguage"]);
    }

    public function displayMenuLanguage($args)
    {
        global $tsm;
        $Polylang = $tsm->getPolylangManager();
        $this->setArguments($args);
        $languages = $Polylang->getLanguagesActives();
        $currentLanguageSlug = $Polylang->getCurrentLanguageSlug();
        ob_start();
        $this->displayContainerStart();
        usort($languages, function ($a, $b) {
            if ($a["default"]) {
                return -1;
            }
            if ($b["default"]) {
                return 1;
            }
            return strcmp($a["name"], $b["name"]);
        });
        $id = get_the_ID();
        if ($id) {
            $this->translations = TranslationPost::findTranslationFor($id);
            $translations = $this->translations;
            array_filter($languages, function ($language) use ($translations) {
                return !empty($translations->getTranslation($language["code"]));
            });
        }
        $this->displayTextMenu($languages[0]);
        $this->displaySubContainerStart();
        foreach ($languages as $language) {
            $isCurrent = $currentLanguageSlug === $language["code"];
            if ($this->hideEmpty && $language["no_translation"]) {
                continue;
            }
            if ($isCurrent) {
                continue;
            }
            $this->displayLanguage($language, $id);
        }
        $this->displaySubContainerEnd();
        $this->displayContainerEnd();
        $this->injectJavascript();
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
        $this->list = $properties["list"] === "true";
        $this->name = $properties["name"] === "true";
        $this->flag = $properties["flag"] === "true";
        $this->hideEmpty = $properties["hideEmpty"] === "true";
        $this->redirectHome = $properties["redirectHome"] === "true";
        $this->hideCurrent = $properties["hideCurrent"] === "true";
    }

    private function displayContainerStart()
    {
        $classList = ["menu-language"];
        if ($this->list) {
            $classList[] = "list";
        }
        echo "<div class='" . implode(" ", $classList) . "'>";
    }

    private function displayTextMenu($language)
    {
        echo "<a href='#'>";
        if ($this->hideCurrent) {
            echo "<span>" . TextDomain::__("Change language") . "</span>";
        } else {
            if ($this->flag) {
                echo $language["flag"];
            }
            if ($this->name) {
                echo $language["name"];
            } else if (!$this->flag) {
                echo $language["code"];
            }
        }
        $this->displayIcon();
        echo "</a>";
    }

    private function displayIcon()
    {
        ?>
        <svg viewBox="0 0 330 512" aria-hidden="true" xmlns="http://www.w3.org/2000/svg" width="1em" height="1em">
            <path d="M305.913 197.085c0 2.266-1.133 4.815-2.833 6.514L171.087 335.593c-1.7 1.7-4.249 2.832-6.515 2.832s-4.815-1.133-6.515-2.832L26.064 203.599c-1.7-1.7-2.832-4.248-2.832-6.514s1.132-4.816 2.832-6.515l14.162-14.163c1.7-1.699 3.966-2.832 6.515-2.832 2.266 0 4.815 1.133 6.515 2.832l111.316 111.317 111.316-111.317c1.7-1.699 4.249-2.832 6.515-2.832s4.815 1.133 6.515 2.832l14.162 14.163c1.7 1.7 2.833 4.249 2.833 6.515z"></path>
        </svg>
        <?php
    }

    private function displaySubContainerStart()
    {
        echo "<div class='sub-menu-language'>";
    }

    private function displayLanguage($language, $id)
    {
        if (!$this->redirectHome && empty($id)) {
            return;
        }
        global $tsm;
        $Polylang = $tsm->getPolylangManager();
        if ($this->redirectHome) {
            $url = $Polylang->getHomeUrl($language["code"]);
        } else {
            $url = get_permalink($this->translations->getTranslation($language["code"]));
        }
        echo '<a href="' . $url . '">';
        if ($this->flag) {
            echo $language["flag"];
        }
        if ($this->name) {
            echo $language["name"];
        } else if (!$this->flag) {
            echo $language["code"];
        }
        echo "</a>";
    }

    private function displaySubContainerEnd()
    {
        echo "</div>";
    }

    private function displayContainerEnd()
    {
        echo "</div>";
    }

    private function injectJavascript()
    {
        ?>
        <script type="text/javascript">
            document.addEventListener("DOMContentLoaded", function () {
                const width = document.querySelector(".menu-language").offsetWidth;
                document.querySelector(".sub-menu-language").style.width = width + "px";
            });
        </script>
        <?php
    }
}

$MenuLanguage = new MenuLanguage();
$MenuLanguage->init();