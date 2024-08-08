<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Bulk;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Icon;
use TraduireSansMigraine\Front\Components\Modal;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Tooltip;use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;

class Dictionary {

    private $path;
    private $name;
    private $glossaries;
    private $languages;


    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Dictionary.js", [], TSM__VERSION);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Dictionary.min.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
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
        add_action("wp_ajax_traduire-sans-migraine_render_dictionary", [$this, "renderDictionary"]);
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    public function renderRow($langFrom, $entry, $result, $_id, $langTo) {
        ?>
        <form class="row-dictionary">
            <select name="langFrom" id="langFrom">
                <?php
                foreach ($this->getLanguages() as $language => $name) {
                    if ($langTo == $language) {
                        $this->name = $name;
                        continue;
                    }
                    if (!isset($this->glossaries[$langTo]) || !in_array($language, $this->glossaries[$langTo])) {
                        continue;
                    }
                    ?>
                    <option value="<?php echo $language; ?>" <?php echo $language == $langFrom ? "selected" : ""; ?>><?php echo $name; ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="<?php echo $entry; ?>" name="entry" id="entry" placeholder="<?php echo TextDomain::__("Original words"); ?>" />
            <input type="text" value="<?php echo $result; ?>" name="result" id="result" placeholder="<?php echo TextDomain::__("Translation in %s", $this->name); ?>" />
            <?php
                Button::render(TextDomain::__("Update"), "primary", "update", [
                    "wp-nonce" => wp_create_nonce("traduire-sans-migraine_update_word_to_dictionary")
                ]);
                Button::render(TextDomain::__("Delete"), "danger", "delete", [
                    "wp-nonce" => wp_create_nonce("traduire-sans-migraine_delete_word_to_dictionary")
                ]);
            ?>
            <input type="hidden" value="<?php echo $_id; ?>" name="_id" id="_id" />
        </form>
        <?php
    }

    public function renderNewRow($langFrom, $langTo) {
        ?>
        <form class="row-dictionary">
            <select name="langFrom" id="langFrom">
                <?php
                foreach ($this->getLanguages() as $language => $name) {
                    if ($langTo == $language) {
                        continue;
                    }
                    if (!isset($this->glossaries[$langTo]) || !in_array($language, $this->glossaries[$langTo])) {
                        continue;
                    }
                    ?>
                    <option value="<?php echo $language; ?>" <?php echo $language == $langFrom ? "selected" : ""; ?>><?php echo $name; ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="text" value="" name="entry" id="entry" placeholder="<?php echo TextDomain::__("Original words"); ?>" />
            <input type="text" value="" name="result" id="result" placeholder="<?php echo TextDomain::__("Translation in %s", $this->name); ?>" />
            <?php
                Button::render(TextDomain::__("Add"), "primary", "add", [
                    "wp-nonce" => wp_create_nonce("traduire-sans-migraine_add_word_to_dictionary")
                ]);
            ?>
        </form>
        <?php
    }

    public function renderDictionary() {
        $langTo = $_GET["language"];
        $clientSeoSansMigraine = new Client();
        $clientSeoSansMigraine->checkCredential();
        $dictionaries = $clientSeoSansMigraine->loadDictionary($langTo);
        $this->name = $this->getLanguages()[$langTo];
        ob_start();
        ?>
        <input type="hidden" name="langTo" id="langTo" value="<?php echo $langTo; ?>" />
        <p>Here you can add words or groups of words to explain how they must be translated into <?php echo $this->name; ?></p>
        <div>
        <?php
        $lastLangFrom = "";
        foreach ($dictionaries as $dictionary) {
            $langFrom = $dictionary["langFrom"];
            $lastLangFrom = $langFrom;
            $entry = $dictionary["entry"];
            $result = $dictionary["result"];
            $_id = $dictionary["_id"];
            $this->renderRow($langFrom, $entry, $result, $_id, $langTo);
        }
        $this->renderNewRow($lastLangFrom, $langTo);
        ?>
        </div>
        <?php
        $content = ob_get_clean();
        Modal::render(TextDomain::__("Dictionary for %s", $this->name), $content);
        wp_die();
    }

    private function getLanguages() {
        if (!empty($this->languages)) {
            return $this->languages;
        }
        $this->initLanguagesAndGlossaries();
        return $this->languages;
    }

    private function initLanguagesAndGlossaries() {
        $clientSeoSansMigraine = new Client();
        $clientSeoSansMigraine->checkCredential();
        $response = $clientSeoSansMigraine->getLanguages();
        $listSlugs = $response["languages"];
        $this->glossaries = [];
        $polylangLanguages = include POLYLANG_DIR . '/settings/languages.php';
        $this->languages = [];
        foreach ($listSlugs as $slug) {
            if ($slug === "en") {
                $locale = "en_US";
            } else {
                $locale = $slug . "_" . strtoupper($slug);
            }
            $associatedLanguage = isset($polylangLanguages[$locale]) ? $polylangLanguages[$locale] : null;
            if (!$associatedLanguage) {
                foreach ($polylangLanguages as $polylangLanguage) {
                    if (isset($polylangLanguage["code"]) && $polylangLanguage["code"] === $slug) {
                        $associatedLanguage = $polylangLanguage;
                        break;
                    }
                }
            }
            if ($associatedLanguage) {
                $this->languages[$slug] = $associatedLanguage["name"];
            } else {
                $this->languages[$slug] = $slug;
            }
        }
        foreach ($response["glossaries"] as $glossary) {
            if (!isset($this->glossaries[$glossary["target_lang"]])) {
                $this->glossaries[$glossary["target_lang"]] = [];
            }
            $this->glossaries[$glossary["target_lang"]][] = $glossary["source_lang"];
        }
    }
}

$Dictionary = new Dictionary();
$Dictionary->init();