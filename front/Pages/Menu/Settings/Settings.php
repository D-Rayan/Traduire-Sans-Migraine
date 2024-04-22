<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Settings;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Suggestions;
use TraduireSansMigraine\Front\Pages\LogIn\LogIn;
use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Settings as SettingsPlugin;
use TraduireSansMigraine\Wordpress\TextDomain;

class Settings {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
        $this->init();
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Settings.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles() {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Settings.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        if (!isset($_GET["page"]) || $_GET["page"] !== "traduire-sans-migraine") {
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
        if (is_admin()) {
            $this->loadAdminHooks();
        } else {
            $this->loadClientHooks();
        }
    }

    public function loadAdminHooks() {
        add_action("wp_ajax_traduire-sans-migraine_update_settings", [$this, "saveSettings"]);
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    public function saveSettings() {
        $settings = [
            "content" => isset($_POST["content"]) && $_POST["content"] === "true",
            "title" => isset($_POST["title"]) && $_POST["title"] === "true",
            "excerpt" => isset($_POST["excerpt"]) && $_POST["excerpt"] === "true",
            "slug" => isset($_POST["slug"]) && $_POST["slug"] === "true",
        ];

        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php")) {
            $settings["_yoast_wpseo_title"] = isset($_POST["_yoast_wpseo_title"]) && $_POST["_yoast_wpseo_title"] === "true";
            $settings["_yoast_wpseo_metadesc"] = isset($_POST["_yoast_wpseo_metadesc"]) && $_POST["_yoast_wpseo_metadesc"] === "true";
            $settings["_yoast_wpseo_metakeywords"] = isset($_POST["_yoast_wpseo_metakeywords"]) && $_POST["_yoast_wpseo_metakeywords"] === "true";
        }

        if (is_plugin_active("rank-math/rank-math.php")) {
            $settings["rank_math_description"] = isset($_POST["rank_math_description"]) && $_POST["rank_math_description"] === "true";
            $settings["rank_math_title"] = isset($_POST["rank_math_title"]) && $_POST["rank_math_title"] === "true";
            $settings["rank_math_focus_keyword"] = isset($_POST["rank_math_focus_keyword"]) && $_POST["rank_math_focus_keyword"] === "true";
        }
        $settingsInstance = new SettingsPlugin();
        $settingsInstance->saveSettings($settings);

        echo json_encode(["success" => true, "data" => TextDomain::__("Your settings have been saved.")]);
        wp_die();
    }

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TSM__NAME; ?></span><br/>
        <span class="second-color"><?php echo TextDomain::__("⚙️ Settings"); ?></span>
        <?php
        return ob_get_clean();
    }

    private static function getStatePlugin() {
        $client = new Client();
        $settingsInstance = new SettingsPlugin();
        $client->fetchAccount();
        $account = $client->getAccount();
        $redirect = $client->getRedirect();
        ob_start();
        if ($account === null && $redirect === null) {
            Alert::render(TextDomain::__("An error occurred"), TextDomain::__("Could not fetch your account."), "error");
        } else if ($redirect !== null) {
            LogIn::render($redirect["url"]);
        } else {
            $quotaMax = $account["quota"]["max"] + $account["quota"]["bonus"];
            $quotaCurrent = $account["quota"]["current"];
            $quotaResetDate = $account["quota"]["resetDate"];
            ;
            $step = Step::getHTML([
                "classname" => "settings-step-progress",
                "indicatorText" => TextDomain::_n("She have translated %s character on %s", "She have translated %s characters on %s", $quotaCurrent, $quotaCurrent, $quotaMax),
                "percentage" => ($quotaCurrent / $quotaMax) * 100 . "%",
            ]);
            Suggestions::render(TextDomain::__("Your otter 🦦"),
                $step,
                "<div class='suggestion-footer-settings'>
                <div>".TextDomain::__("She will reset the %s", date("d/m/y", strtotime($quotaResetDate)))."</div>
                <div class='right-footer'>
                    <img width='72' src='".TSM__ASSETS_PATH."loutre_ampoule.png' alt='loutre_ampoule' />"
                    . Button::getHTML(TextDomain::__("Need more?"), "primary", "upgrade-quota", [
                            "href" => TSM__CLIENT_LOGIN_DOMAIN . "?key=" . $settingsInstance->getToken(),
                ])
                . "</div></div>",
                [ "classname" => "suggestion-settings"]
            );
        }
        return ob_get_clean();
    }

    private static function getParametersPlugin() {
        $settingsInstance = new SettingsPlugin();
        ob_start();
        $settings = [
            "content" => [
                "checked" => $settingsInstance->settingIsEnabled("content"),
                "label" => TextDomain::__("Post's content"),
            ],
            "title" => [
                "checked" => $settingsInstance->settingIsEnabled("title"),
                "label" => TextDomain::__("Post's title"),
            ],
            "excerpt" => [
                "checked" => $settingsInstance->settingIsEnabled("excerpt"),
                "label" => TextDomain::__("Post's Excerpt"),
            ],
            "slug" => [
                "checked" => $settingsInstance->settingIsEnabled("slug"),
                "label" => TextDomain::__("Post's slug"),
            ],
        ];

        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php")) {
            $settings["_yoast_wpseo_title"] = [
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_title"),
                "label" => TextDomain::__("SEO Title"),
            ];
            $settings["_yoast_wpseo_metadesc"] = [
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_metadesc"),
                "label" => TextDomain::__("SEO Description"),
            ];
            $settings["_yoast_wpseo_metakeywords"] = [
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_metakeywords"),
                "label" => TextDomain::__("Keywords"),
            ];
        }

        if (is_plugin_active("rank-math/rank-math.php")) {
            $settings["rank_math_description"] = [
                "checked" => $settingsInstance->settingIsEnabled("rank_math_description"),
                "label" => TextDomain::__("SEO Title"),
            ];
            $settings["rank_math_title"] = [
                "checked" => $settingsInstance->settingIsEnabled("rank_math_title"),
                "label" => TextDomain::__("SEO Description"),
            ];
            $settings["rank_math_focus_keyword"] = [
                "checked" => $settingsInstance->settingIsEnabled("rank_math_focus_keyword"),
                "label" => TextDomain::__("Keywords"),
            ];
        }
        ?>
        <div class="preferences">
            <div class="title"><?php echo TextDomain::__("Settings"); ?></div>
            <div class="description"><?php echo TextDomain::__("What the otter should update when the post already exists?"); ?></div>
            <div class="content">
                <div class="settings">
                    <?php
                        foreach ($settings as $key => $setting) {
                            echo "<div class='setting'>";
                            Checkbox::render($setting["label"], $key, $setting["checked"]);
                            echo "</div>";
                        }
                    ?>
                    <div>
                        <?php Button::render(TextDomain::__("Save"), "primary", "save-settings"); ?>
                    </div>
                </div>
                <div>
                    <img src="<?php echo TSM__ASSETS_PATH; ?>loutre_ordinateur.jpg" />
                </div>
            </div>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function getContent() {
        ob_start();
        ?>
        <div class="container">
        <?php
        echo self::getStatePlugin();
        echo self::getParametersPlugin();
        ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function getDescription() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Here you can configure the tool to make it perfect for your needs."); ?></span>
        <?php
        return ob_get_clean();
    }
    static function render() {
        $content = self::getContent();
        $title = self::getTitle();
        $description = self::getDescription();
        Menu::render($title, $description, $content, "loutre_docteur.png");
    }
}

$settings = new Settings();
$settings->init();

