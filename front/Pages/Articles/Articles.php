<?php

namespace TraduireSansMigraine\Front\Pages\Articles;

use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Modal;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\TextDomain;

class Articles {

    private $path;
    private $languageManager;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Articles.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Articles.min.css", [], TSM__VERSION);
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
        add_action("wp_ajax_traduire-sans-migraine_article_deleted_render", [$this, "render"]);
        add_action("wp_ajax_traduire-sans-migraine_article_deleted_delete_translations", [$this, "deleteTranslations"]);
    }

    public function loadClientHooks() {
        // nothing here
    }

    public function onlyOneArticleHasBeenDeleted() {
        return isset($_GET["trashed"]) && isset($_GET["ids"]) && count(explode(",", $_GET["ids"])) === 1;
    }

    public function countArticlesTranslated($id) {
        $translations = $this->getLanguageManager()->getAllTranslationsPost($id);
        $total = 0;
        foreach ($translations as $translation) {
            if ($translation["postId"] != $id && $translation["postId"] > 0) {
                $total++;
            }
        }

        return $total;
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    private function getLanguageManager() {
        if (!isset($this->languageManager)) {
            $this->languageManager = new LanguageManager();
        }

        return $this->languageManager->getLanguageManager();
    }

    public function render() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_article_deleted_render")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["post_id"])) {
            Modal::render(TSM__NAME, Alert::getHTML(TSM__NAME, TextDomain::__("Post ID is not set"), "danger"));
            wp_die();
        }

        $localPostId = $_GET["post_id"];
        if (get_post_status( $localPostId ) !== "trash") {
            wp_send_json_error(["message" => "Should be trash"]);
            wp_die();
        }
        $countArticlesTranslated = $this->countArticlesTranslated($localPostId);
        if ($countArticlesTranslated === 0) {
            wp_send_json_error(["message" => "No translations"]);
            wp_die();
        }
        ob_start();
        echo TextDomain::__("You have moved to the trash one publication.");
        echo TextDomain::_n("Do you wish to also set the translation into the trash?", "Do you wish to also set the %n translations into the trash?", $countArticlesTranslated, $countArticlesTranslated);
        ?>
        <div class="buttons-actions">
            <?php
            Button::render(TextDomain::__("No"), "primary", "no-button");
            Button::render(TextDomain::__("Yes"), "danger", "yes-button", [
                "wp_nonce" => wp_create_nonce("traduire-sans-migraine_article_deleted_delete_translations")
            ]);
            ?>
        </div>
        <?php
        $htmlContent = ob_get_clean();
        Modal::render(TSM__NAME, $htmlContent, [], [ "size" => "little" ]);
        wp_die();
    }

    public function deleteTranslations() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_article_deleted_delete_translations")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        if (!isset($_GET["post_id"])) {
            wp_send_json_error(["html" => Alert::getHTML(false, TextDomain::__("Post ID is not set"), "danger")]);
            wp_die();
        }

        $localPostId = $_GET["post_id"];
        if (get_post_status( $localPostId ) !== "trash") {
            wp_send_json_error(["html" => Alert::getHTML(false, TextDomain::__("The original publication isn't in the trash."), "danger")]);
            wp_die();
        }
        $countArticlesTranslated = $this->countArticlesTranslated($localPostId);
        if ($countArticlesTranslated === 0) {
            wp_send_json_error(["html" => Alert::getHTML(false, TextDomain::__("No Translations to delete."), "danger")]);
            wp_die();
        }

        $translations = $this->getLanguageManager()->getAllTranslationsPost($localPostId);
        foreach ($translations as $translation) {
            if ($translation["postId"] != $localPostId && $translation["postId"] > 0) {
                wp_trash_post( $translation["postId"] );
            }
        }
        wp_send_json_success(["html" => Alert::getHTML(false, TextDomain::_n("The translation has been moved to the trash.", "The %n translations have been moved to the trash.", $countArticlesTranslated), "success", ["isDismissible" => false])]);
        wp_die();
    }
}

$articles = new Articles();
$articles->init();