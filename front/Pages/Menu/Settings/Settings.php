<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Settings;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Suggestions;
use TraduireSansMigraine\Front\Components\Tooltip;
use TraduireSansMigraine\Front\Pages\LogIn\LogIn;
use TraduireSansMigraine\Front\Pages\Menu\Bulk\Dictionary;
use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\Languages\LanguageManager;
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
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Settings.min.css", [], TSM__VERSION);
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
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine-update-settings")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $settings = [
            "content" => isset($_POST["content"]) && $_POST["content"] === "true",
            "title" => isset($_POST["title"]) && $_POST["title"] === "true",
            "slug" => isset($_POST["slug"]) && $_POST["slug"] === "true",
            "excerpt" => isset($_POST["excerpt"]) && $_POST["excerpt"] === "true",
            "translateAssets" => isset($_POST["translateAssets"]) && $_POST["translateAssets"] === "true",
        ];

        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $settings["_yoast_wpseo_title"] = isset($_POST["_yoast_wpseo_title"]) && $_POST["_yoast_wpseo_title"] === "true";
            $settings["_yoast_wpseo_metadesc"] = isset($_POST["_yoast_wpseo_metadesc"]) && $_POST["_yoast_wpseo_metadesc"] === "true";
            $settings["_yoast_wpseo_metakeywords"] = isset($_POST["_yoast_wpseo_metakeywords"]) && $_POST["_yoast_wpseo_metakeywords"] === "true";
            $settings["yoast_wpseo_focuskw"] = isset($_POST["yoast_wpseo_focuskw"]) && $_POST["yoast_wpseo_focuskw"] === "true";
        }

        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $settings["rank_math_description"] = isset($_POST["rank_math_description"]) && $_POST["rank_math_description"] === "true";
            $settings["rank_math_title"] = isset($_POST["rank_math_title"]) && $_POST["rank_math_title"] === "true";
            $settings["rank_math_focus_keyword"] = isset($_POST["rank_math_focus_keyword"]) && $_POST["rank_math_focus_keyword"] === "true";
        }

        if (is_plugin_active("wp-seopress/seopress.php")) {
            $settings["seopress_titles_desc"] = isset($_POST["seopress_titles_desc"]) && $_POST["seopress_titles_desc"] === "true";
            $settings["seopress_titles_title"] = isset($_POST["seopress_titles_title"]) && $_POST["seopress_titles_title"] === "true";
            $settings["seopress_analysis_target_kw"] = isset($_POST["seopress_analysis_target_kw"]) && $_POST["seopress_analysis_target_kw"] === "true";
        }

        // $settings["tsmOpenOnSave"] = isset($_POST["tsmOpenOnSave"]) && $_POST["tsmOpenOnSave"] === "true";


        $settingsInstance = new SettingsPlugin();
        $settingsInstance->saveSettings($settings);

        echo json_encode(["success" => true, "data" => TextDomain::__("Your settings have been saved.")]);
        wp_die();
    }

    private static function getTitle($account, $countLanguages) {
        ob_start();
        if ($account === null) {
            ?>
            <span><?php echo TSM__NAME; ?></span>
            <span class="second-color"><?php echo TextDomain::__("Installation 1/2"); ?></span>
            <?php
        } else if ($countLanguages < 2) {
            ?>
            <span><?php echo TSM__NAME; ?></span>
            <span class="second-color"><?php echo TextDomain::__("Installation 2/2"); ?></span>
            <?php
        } else {
            ?>
            <span><?php echo TSM__NAME; ?></span>
            <span class="second-color"><?php echo TextDomain::__("⚙️ Settings"); ?></span>
            <?php
        }
        return ob_get_clean();
    }

    private static function getStatePlugin($account, $redirect) {
        $settingsInstance = new SettingsPlugin();
        ob_start();
        if ($account === null && $redirect === null) {
            Alert::render(TextDomain::__("An error occurred"), TextDomain::__("Could not fetch your account."), "error");
        } else if ($redirect !== null) {
            LogIn::render();
        } else {
            $quotaMax = $account["quota"]["max"] + $account["quota"]["bonus"];
            $quotaCurrent = $account["quota"]["current"];
            $quotaResetDate = $account["quota"]["resetDate"];
            ;
            $step = Step::getHTML([
                "classname" => "settings-step-progress",
                "indicatorText" => TextDomain::_n("She have translated %s character on %s", "She have translated %s characters on %s", $quotaCurrent, displayBigNumber($quotaCurrent), displayBigNumber($quotaMax)),
                "percentage" => ($quotaCurrent / $quotaMax) * 100 . "%",
            ]);
            Suggestions::render(TextDomain::__("Your otter 🦦"),
                $step,
                "<div class='suggestion-footer-settings'>
                <div>".TextDomain::__("She will reset the %s", date("d/m/Y", strtotime($quotaResetDate)))."</div>
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
                "tooltip" => TextDomain::__("The excerpt is a short summary of your post. It is used by default in the search results.")
            ],
            "slug" => [
                "checked" => $settingsInstance->settingIsEnabled("slug"),
                "label" => TextDomain::__("Post's slug"),
                "tooltip" => TextDomain::__("The slug is the URL of your post.")
            ],
            "translateAssets" => [
                "checked" => $settingsInstance->settingIsEnabled("translateAssets"),
                "label" => TextDomain::__("Translate media assets"),
                "tooltip" => TextDomain::__("Translate the alt and the name of your media assets used inside your content.")
            ],
        ];

        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php") || defined("WPSEO_FILE")) {
            $settings["_yoast_wpseo_title"] = [
                "before" => TextDomain::__("Translate the options for Yoast SEO"),
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_title"),
                "label" => TextDomain::__("SEO Title"),
                "tooltip" => TextDomain::__("The title is the title of your post. It is used in the search results.")
            ];
            $settings["_yoast_wpseo_metadesc"] = [
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_metadesc"),
                "label" => TextDomain::__("SEO Description"),
                "tooltip" => TextDomain::__("The description is a short summary of your post. It is used in the search results.")
            ];
            $settings["_yoast_wpseo_metakeywords"] = [
                "checked" => $settingsInstance->settingIsEnabled("_yoast_wpseo_metakeywords"),
                "label" => TextDomain::__("Keywords"),
                "tooltip" => TextDomain::__("Keywords are used by yoast to help you to optimize your post.")
            ];
            $settings["yoast_wpseo_focuskw"] = [
                "checked" => $settingsInstance->settingIsEnabled("yoast_wpseo_focuskw"),
                "label" => TextDomain::__("Target Request"),
                "tooltip" => TextDomain::__("The target request is the request you want to rank for. It will help you to optimize your post.")
            ];
        }

        if (is_plugin_active("seo-by-rank-math/rank-math.php") || function_exists("rank_math")) {
            $settings["rank_math_description"] = [
                "before" => TextDomain::__("Translate the options for Rank Math"),
                "checked" => $settingsInstance->settingIsEnabled("rank_math_description"),
                "label" => TextDomain::__("SEO Title"),
                "tooltip" => TextDomain::__("The title is the title of your post. It is used in the search results.")
            ];
            $settings["rank_math_title"] = [
                "checked" => $settingsInstance->settingIsEnabled("rank_math_title"),
                "label" => TextDomain::__("SEO Description"),
                "tooltip" => TextDomain::__("The description is a short summary of your post. It is used in the search results.")
            ];
            $settings["rank_math_focus_keyword"] = [
                "checked" => $settingsInstance->settingIsEnabled("rank_math_focus_keyword"),
                "label" => TextDomain::__("Keywords"),
                "tooltip" => TextDomain::__("Keywords are used by rank math to help you to optimize your post.")
            ];
        }

        if (is_plugin_active("wp-seopress/seopress.php")) {
            $settings["seopress_titles_desc"] = [
                "before" => TextDomain::__("Translate the options for SEOPress"),
                "checked" => $settingsInstance->settingIsEnabled("seopress_titles_desc"),
                "label" => TextDomain::__("SEO Title"),
                "tooltip" => TextDomain::__("The title is the title of your post. It is used in the search results.")
            ];
            $settings["seopress_titles_title"] = [
                "checked" => $settingsInstance->settingIsEnabled("seopress_titles_title"),
                "label" => TextDomain::__("SEO Description"),
                "tooltip" => TextDomain::__("The description is a short summary of your post. It is used in the search results.")
            ];
            $settings["seopress_analysis_target_kw"] = [
                "checked" => $settingsInstance->settingIsEnabled("seopress_analysis_target_kw"),
                "label" => TextDomain::__("Keywords"),
                "tooltip" => TextDomain::__("Keywords are used by SEOPress to help you to optimize your post.")
            ];
        }
        ?>
        <div class="preferences">
            <div class="title"><?php echo TextDomain::__("Settings"); ?></div>
            <div class="description"><?php echo TextDomain::__("What the otter should update when the content already exists?"); ?></div>
            <div class="content">
                <div class="settings">
                    <?php
                        foreach ($settings as $key => $setting) {
                            echo "<div class='setting'>";
                            if (isset($setting["before"])) {
                                echo "<div class='before'>".$setting["before"]."</div>";
                            }
                            echo "<div class='row'>";
                            $tooltipHTML = "";
                            if (isset($setting["tooltip"])) {
                                $tooltipHTML = Tooltip::getHTML(
                                    '<svg viewBox="64 64 896 896" focusable="false" data-icon="info-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path><path d="M464 336a48 48 0 1096 0 48 48 0 10-96 0zm72 112h-48c-4.4 0-8 3.6-8 8v272c0 4.4 3.6 8 8 8h48c4.4 0 8-3.6 8-8V456c0-4.4-3.6-8-8-8z"></path></svg>',
                                    Alert::getHTML(null, $setting["tooltip"], "info", [
                                        "isDismissible" => false,
                                    ])
                                );
                            }
                            Checkbox::render($setting["label"] . $tooltipHTML, $key, $setting["checked"]);
                            echo "</div>";
                            echo "</div>";
                        }
                    ?>
                    <div>
                        <?php Button::render(TextDomain::__("Save"), "primary", "save-settings", [
                                "wp_nonce" => wp_create_nonce("traduire-sans-migraine-update-settings"),
                        ]); ?>
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

    private static function renderLanguagesSettings() {
        $languageManager = new LanguageManager();
        $client = Client::getInstance();
        $client->fetchAccount();
        $account = $client->getAccount();
        if ($account === null) {
            return;
        }
        $languagesAvailable = $languageManager->getLanguageManager()->getLanguages();
        $glossaries = $client->getLanguages()["glossaries"];
        $slugs = $account["slugs"];
        $maxSlugs = $account["slugs"]["max"] == -1 ? PHP_INT_MAX : $account["slugs"]["max"];
        $maxSlugsString = $account["slugs"]["max"] == -1 ? TextDomain::__("Unlimited") : $account["slugs"]["max"];
        $currentSlugs = $account["slugs"]["current"];
        $allOptions = isset($slugs["options"]) ? $slugs["options"] : [];
        ?>
        <div class="languages-settings">
            <div class="title"><?php echo TextDomain::__("Gérer vos langues"); ?> (<?php echo $currentSlugs . " / " . $maxSlugsString; ?>)</div>
            <?php
            foreach ($slugs["allowed"] as $languageCode) {
                $isEnabledInThisWebsite = false;
                $slugsUsed[$languageCode] = true;
                $options = isset($allOptions[$languageCode]) ? $allOptions[$languageCode] : [];
                $glossaryAvailable = false;
                $formalityAvailable = false;
                $multipleCountry = 0;
                foreach ($languagesAvailable as $subLanguage) {
                    if ($subLanguage["slug"] === $languageCode) {
                        $isEnabledInThisWebsite = $subLanguage["enabled"] || $isEnabledInThisWebsite;
                        $multipleCountry++;
                        $formalityAvailable = $subLanguage["supports_formality"] == true;
                        $languageName = $subLanguage["simple_name"];
                        $locale = $subLanguage["locale"];
                    }
                }
                if ($multipleCountry === 0) {
                    continue;
                }
                foreach ($glossaries as $glossary) {
                    if ($glossary["target_lang"] === $languageCode) {
                        $glossaryAvailable = true;
                        break;
                    }
                }
                ?>
                <div class="language-settings">
                    <span><?php echo $languageName; ?></span>
                    <?php
                    if (!$isEnabledInThisWebsite) {
                        Button::render(TextDomain::__("Enable on this website"), "primary", "add-language", [
                            "language" => $locale,
                            "nonce" => wp_create_nonce("traduire-sans-migraine_add_new_language")
                        ]);
                    } else {
                        if ($glossaryAvailable) {
                            Button::render(TextDomain::__("Open Dictionary"), "primary", "dictionary-button", [
                                "language" => $languageCode,
                            ]);
                        }
                        if ($formalityAvailable) {
                            $formality = isset($options["formality"]) ? $options["formality"] : "default";
                            ?>
                            <select id="formality" name="formality" data-slug="<?php echo $languageCode; ?>" data-nonce="<?php echo wp_create_nonce("traduire-sans-migraine_update_language_settings"); ?>">
                                <option value="default" <?php if ($formality === "default") { echo "selected"; } ?>><?php echo TextDomain::__("Default"); ?></option>
                                <option value="more" <?php if ($formality === "more") { echo "selected"; } ?>><?php echo TextDomain::__("More Formal"); ?></option>
                                <option value="less" <?php if ($formality === "less") { echo "selected"; } ?>><?php echo TextDomain::__("Less Formal"); ?></option>
                            </select>
                            <?php
                        }
                        if ($multipleCountry > 1) {
                            $selectedCountry = isset($options["country"]) ? $options["country"] : "";
                            ?>
                            <select id="country" name="country" data-slug="<?php echo $languageCode; ?>" data-nonce="<?php echo wp_create_nonce("traduire-sans-migraine_update_language_settings"); ?>">
                                <?php
                                foreach ($languagesAvailable as $lang) {
                                    if ($lang["slug"] !== $languageCode) {
                                        continue;
                                    }
                                    ?>
                                    <option value="<?php echo $lang["locale"]; ?>" <?php if ($selectedCountry === $lang["locale"]) { echo "selected"; } ?>><?php echo $lang["name"]; ?></option>
                                    <?php
                                }
                                ?>
                            </select>
                            <?php
                        }
                    }
                    ?>
                </div>
                <?php
            }
            if ($currentSlugs < $maxSlugs) {
                ?>
                <div class="row-add-language">
                    <label for="language-selection-add"><?php echo TextDomain::__("If you want you can add a new language to your website"); ?></label>
                    <select id="language-selection-add" class="global-languages">
                        <?php
                        $uniquesSlugs = [];
                        foreach ($languagesAvailable as $language) {
                            if (isset($uniquesSlugs[$language["slug"]])) {
                                continue;
                            }
                            $uniquesSlugs[$language["slug"]] = true;
                            if ($language["enabled"]) {
                                continue;
                            }
                            ?>
                            <option value="<?php echo $language["locale"]; ?>">
                                <?php echo $language["simple_name"]; ?>
                            </option>
                            <?php
                        }
                        ?>
                    </select>
                    <?php
                    Button::render(TextDomain::__("Add"), "primary", "add-new-language", [
                        "nonce" => wp_create_nonce("traduire-sans-migraine_add_new_language")
                    ]);
                    ?>
                </div>
                <?php
            } else {
                Suggestions::render(
                    TextDomain::__("You have reached the maximum languages you can translate."),
                    TextDomain::__("If you want more languages, you can ")
                    . Button::getHTML(TextDomain::__("Upgrade your plan"), "primary", "upgrade-plan-button", [
                        "href" => TSM__CLIENT_LOGIN_DOMAIN
                    ]),
                    "<img width='72' src='" . TSM__ASSETS_PATH . "loutre_ampoule.png' alt='loutre_ampoule' />");
            }
            ?>
        </div>
        <?php
    }

    private static function renderLogInSection($redirect) {
        ?>
        <div class="container">
            <?php
                echo self::getStatePlugin(null, $redirect);
            ?>
        </div>
        <?php
    }
    private static function renderLanguagesInitialization() {
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguages();
        $activeLanguage = null;
        foreach ($languages as $language) {
            if ($language["enabled"]) {
                $activeLanguage = $language;
                break;
            }
        }
        ob_start();
        ?>
        <div class="container">
            <div class="column">
                <div class="preferences">
                    <div class="title"><?php echo TextDomain::__("Languages initialization"); ?></div>
                    <div class="description"><?php if ($activeLanguage) { echo TextDomain::__("You already have enabled %s on your website, just one more language and you'll be able to use this tool.", $activeLanguage["simple_name"]); } else { echo TextDomain::__("You need at least two languages to use the tool. Please add a new language to your website."); } ?></div>
                    <div class="content">
                        <div class="row-add-language">
                            <label for="language-selection-add"><?php echo TextDomain::__("Just select the language then add it."); ?></label>
                            <select id="language-selection-add" class="global-languages">
                                <?php
                                $uniqueSlugs = [];
                                foreach ($languages as $language) {
                                    if (isset($uniqueSlugs[$language["slug"]])) {
                                        continue;
                                    }
                                    if ($language["enabled"]) {
                                        continue;
                                    }
                                    $preSelected = ($activeLanguage && $activeLanguage["locale"] === get_locale() && ($language["slug"] === "fr" || $language["slug"] === "en"))
                                        || ((!$activeLanguage || $activeLanguage["locale"] !== get_locale()) && $language["locale"] === get_locale());
                                    $uniqueSlugs[$language["slug"]] = true;
                                    ?>
                                    <option value="<?php echo $language["locale"]; ?>" <?php if ($preSelected) { echo "selected"; } ?>>
                                        <?php echo $language["simple_name"]; ?>
                                    </option>
                                    <?php
                                }
                                ?>
                            </select>
                            <?php
                            Button::render(TextDomain::__("Add"), "primary", "add-new-language", [
                                "nonce" => wp_create_nonce("traduire-sans-migraine_add_new_language")
                            ]);
                            ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        <?php
        echo ob_get_clean();
    }
    private static function getContent($account, $redirect, $countLanguages) {
        ob_start();
        if (!$account) {
            self::renderLogInSection($redirect);
        } else {
            if ($countLanguages < 2) {
                self::renderLanguagesInitialization();
            } else {
                ?>
                <div class="container">
                    <div class="column">
                        <?php
                        echo self::getParametersPlugin();
                        self::renderLanguagesSettings();
                        ?>
                    </div>
                    <div class="column">
                        <?php
                        echo self::getStatePlugin($account, $redirect);
                        echo self::getHelpPlugin();
                        ?>
                    </div>
                </div>
                <?php
            }
        }
        return ob_get_clean();
    }

    private static function getHelpPlugin() {
        ob_start();
        ?>
        <video style="max-width: 45vw;" width="90%" controls>
            <source src="<?php echo TSM__URL_DOMAIN . "/wp-content/uploads/products/traduire-sans-migraine/server-assets/tutoriel.mov"; ?>" type="video/mp4">
            Your browser does not support the video tag.
        </video>
        <?php
        return ob_get_clean();
    }

    private static function getDescription($account, $countLanguages) {
        ob_start();
        if ($account === null) {
            ?>
            <span><?php echo TextDomain::__("To continue, you need to authenticate your account."); ?></span>
            <?php
        } else if ($countLanguages < 2) {
            ?>
            <span><?php echo TextDomain::__("Just one more thing, configure your first two languages."); ?></span>
            <?php
        } else {
            ?>
            <span><?php echo TextDomain::__("Here you can configure the tool to make it perfect for your needs."); ?></span>
            <?php
        }
        return ob_get_clean();
    }
    static function render() {
        $client = Client::getInstance();
        $client->fetchAccount();
        $redirect = $client->getRedirect();
        $account = $client->getAccount();
        $languages = [];

        if ($account) {
            $languageManager = new LanguageManager();
            try {
                $languages = $languageManager->getLanguageManager()->getLanguagesActives();
            } catch (\Exception $e) {
                $languages = [];
            }
        }
        $countLanguages = count($languages);

        $content = self::getContent($account, $redirect, $countLanguages);
        $title = self::getTitle($account, $countLanguages);
        $description = self::getDescription($account, $countLanguages);
        Menu::render($title, $description, $content);
    }
}

$settings = new Settings();
$settings->init();

