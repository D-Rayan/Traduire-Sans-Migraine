<?php

namespace TraduireSansMigraine\Front\Pages\Editor\OnSave;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Modal;
use TraduireSansMigraine\Front\Components\Suggestions;
use TraduireSansMigraine\Front\Components\Tooltip;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\TextDomain;

class OnSave {

    private $path;
    private $clientSeoSansMigraine;

    private $linkManager;
    private $languageManager;
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
        $this->clientSeoSansMigraine = new Client();
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "OnSave.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "OnSave.min.css", [], TSM__VERSION);
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

    public function loadHooksAdmin() {
        add_action("wp_ajax_traduire-sans-migraine_editor_onSave_render", [$this, "render"]);
    }

    private function getTranslationsPostsData($post) {
        $languagesTranslatable = $this->clientSeoSansMigraine->getLanguages();
        $translationsPosts = $this->getLanguageManager()->getAllTranslationsPost($post["id"]);
        $enrichedTranslationsPosts = [];
        foreach ($translationsPosts as $codeSlug => $translationPost) {
            $translatable = in_array($translationPost["code"], $languagesTranslatable);
            $postExists = $translationPost["postId"] && get_post_status($translationPost["postId"]) !== "trash";
            $checked = !$postExists && $translationPost["postId"] != $post["id"] && $translatable;
            $issuesTranslatedUrls = $this->getLinkManager()->getIssuedInternalLinks($post["content"], $post["language"], $translationPost["code"]);
            $notTranslated = $issuesTranslatedUrls["notTranslated"];
            $notPublished = $issuesTranslatedUrls["notPublished"];
            $haveWarnings = count($notTranslated) + count($notPublished) > 0;

            $enrichedTranslationsPosts[$codeSlug] = [
                "name" => $translationPost["name"],
                "flag" => $translationPost["flag"],
                "code" => $translationPost["code"],
                "postId" => $postExists ? $translationPost["postId"] : null,
                "checked" => $checked,
                "haveWarnings" => $haveWarnings,
                "notTranslated" => $notTranslated,
                "notPublished" => $notPublished,
                "translatable" => $translatable,
            ];
        }

        usort($enrichedTranslationsPosts, function ($a, $b) use ($post) {
            if ($a["translatable"] && !$b["translatable"])
                return -1;

            if (!$a["translatable"] && $b["translatable"])
                return 1;

            if ($a["postId"] == $post["id"])
                return -1;
            if ($b["postId"] == $post["id"])
                return 1;

            if ($a["haveWarnings"] && !$b["haveWarnings"])
                return 1;

            if (!$a["haveWarnings"] && $b["haveWarnings"])
                return -1;

            if ($a["checked"] && !$b["checked"])
                return 1;

            if (!$a["checked"] && $b["checked"])
                return -1;

            return $a["name"] > $b["name"] ? 1 : -1;
        });

        return $enrichedTranslationsPosts;
    }

    private function renderCurrentPostInformation($post) {
        $listsUrlsIssues = $this->getLinkManager()->extractAndRetrieveInternalLinks($post["content"], $post["language"], true);
        Alert::render(false, TextDomain::__("That's the last version of your post that will be translated. If you have done any modification don't forget to save it before the translation!"), "success", [
            "isDismissible" => false
        ]);
        if (count($listsUrlsIssues) > 0) {
            $listHTML = "<ul>";
            foreach ($listsUrlsIssues as $url => $state) {
                $listHTML .= "<li>".$url."</li>";
            }
            $listHTML .= "</ul>";
            Tooltip::render(
                "<span class='warning-issues'>" . TextDomain::_n("🔎 We found %s issue", "🔎 We found %s issues", count($listsUrlsIssues), count($listsUrlsIssues))  . "</span>",
                Alert::getHTML(TextDomain::__("Oops! Something wrong"), TextDomain::__("The followings articles will not be translated cause we could not find them : %s", $listHTML), "warning", [
                    "isDismissible" => false
                ]));
        }
    }

    private function renderTranslatablePostInformation($postTranslationData) {
        $indicatorText = TextDomain::__("We are impatient to help you with your translations! Just click the translate button.");
        if ($postTranslationData["haveWarnings"]) {
            $listHTML = "<ul>";
            if (count($postTranslationData["notTranslated"]) > 0) {
                foreach ($postTranslationData["notTranslated"] as $url => $postId) {
                    $listHTML .= "<li><a href='".$url."' target='_blank'>" . $url . "</a></li>";
                }
            }
            if (count($postTranslationData["notPublished"]) > 0) {
                foreach ($postTranslationData["notPublished"] as $url => $postId) {
                    $listHTML .= "<li><a href='".$url."' target='_blank'>" . $url . "</a></li>";
                }
            }
            $listHTML .= "</ul>";
            $indicatorText .= "<br/>" . Tooltip::getHTML(
                    "<span class='warning-issues'>" . TextDomain::_n("🔎 We found %s issue", "🔎 We found %s issues", count($postTranslationData["notPublished"]) + count($postTranslationData["notTranslated"]), count($postTranslationData["notPublished"]) + count($postTranslationData["notTranslated"]))  . "</span>",
                    Alert::getHTML(TextDomain::__("Oops! Something wrong"), TextDomain::__("The following links will not be translated because they doesn't exist or aren't published : %s", $listHTML), "warning", [
                        "isDismissible" => false
                    ]));
        }
        Step::render([
            "classname" => $postTranslationData["checked"] ? "":  "hidden",
            "indicatorText" => $indicatorText
        ]);
        if ($postTranslationData["checked"]) {
            Alert::render(false, TextDomain::__("Use the checkbox on the left to add this language to the list of translations, it will create a new article in %s", $postTranslationData["name"]), "primary", [
                "isDismissible" => false,
                "classname" => $postTranslationData["checked"] ? "hidden" : ""
            ]);
        } else {
            Alert::render(false, TextDomain::__("Use the checkbox on the left to add this language to the list of translations. It will overwrite the current translation in %s", $postTranslationData["name"]), "primary", [
                "isDismissible" => false,
                "classname" => $postTranslationData["checked"] ? "hidden" : ""
            ]);
        }
    }

    public function render() {
        if (!isset($_GET["post_id"])) {
            wp_send_json_error([
                "message" => TextDomain::__("We could not find the post. Try again later..."),
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            return;
        }

        $localPostId = $_GET["post_id"];
        if (get_post_status( $localPostId ) === "trash") {
            return;
        }

        $currentPost = [
            "id" => $localPostId
        ];

        try {
            $currentPost["content"] = get_post($localPostId)->post_content;
            $currentPost["language"] = $this->getLanguageManager()->getLanguageForPost($localPostId);
        } catch (\Exception $e) {
            wp_send_json_error([
                "message" => TextDomain::__("You need at least to add two languages to your website. (The default language and the one you want to translate to)"),
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }

        if (empty($currentPost["language"])) {
            wp_send_json_error([
                "title" => TextDomain::__("An error occurred"),
                "message" => TextDomain::__("This post is not associated with a language. Check the configuration and try again."),
                "logo" => "loutre_triste.png"
            ], 400);
            wp_die();
        }
        ob_start();
        ?>
        <div class="language" id="global-languages-section">
            <div class="left-column">
                <?php
                Checkbox::render(
                    TextDomain::__("Unselect/Select All"),
                    "global-languages"
                );
                ?>
            </div>
        </div>
        <div class="traduire-sans-migraine-list-languages">
        <?php
            try {
                $enrichedTranslationsPosts = $this->getTranslationsPostsData($currentPost);
                foreach ($enrichedTranslationsPosts as $enrichedTranslationPost) {
                    ?>
                    <div class="language" data-language="<?php echo $enrichedTranslationPost["code"]; ?>">
                        <div class="left-column">
                            <?php
                                Checkbox::render(
                                    $enrichedTranslationPost["flag"] . " " . $enrichedTranslationPost["name"],
                                    $enrichedTranslationPost["code"],
                                    $enrichedTranslationPost["checked"],
                                    $enrichedTranslationPost["postId"] == $localPostId || !$enrichedTranslationPost["translatable"]
                                );
                            ?>
                        </div>
                        <div class="right-column">
                            <?php
                            if ($enrichedTranslationPost["postId"] == $localPostId) {
                                $this->renderCurrentPostInformation($currentPost);
                            } else if ($enrichedTranslationPost["translatable"]) {
                                $this->renderTranslatablePostInformation($enrichedTranslationPost);
                            } else {
                                Alert::render(false, TextDomain::__("At the moment none of our otters are able to write in this language."), "info", [
                                    "isDismissible" => false
                                ]);
                            }
                            ?>
                        </div>
                    </div>
                    <?php
                }
            } catch (\Exception $e) {
                ob_end_clean();
                return;
            }
        ?>
        </div>
        <?php
        Suggestions::render(
            TextDomain::__("You're not finding a specific language?"),
            TextDomain::__("To translate you first need to add the language to your website.")
            . "<br/>"
            . TextDomain::__("Go to %s to add a new language.", $this->getLanguageManager()->getLanguageManagerName())
            . "<br/>"
            . TextDomain::__("Then come back here to translate your post."),
            "<img width='72' src='".TSM__ASSETS_PATH."loutre_ampoule.png' alt='loutre_ampoule' />");
        $htmlContent = ob_get_clean();
        Modal::render(TSM__NAME, $htmlContent, [
            Button::getHTML(TextDomain::__("Translate now"), "success", "translate-button", [
                    "logged" => $this->clientSeoSansMigraine->checkCredential() ? "true" : "false",
            ]),
            Button::getHTML(TextDomain::__("Close"), "ghost", "closing-button"),
            Tooltip::getHTML(Button::getHTML(TextDomain::__("Debug Helper"), "warning", "debug-button"), Alert::getHTML(TextDomain::__("Debug Helper"), TextDomain::__("This button is only for debug purpose. It will send the data of your content to our service. The data will be deleted after analysis."), "warning", [ "isDismissible" => false ])),
        ]);
        wp_die();
    }

    public function loadHooksClient() {
        // nothing to load
    }

    public function loadHooks() {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }
    public function init() {
        $this->loadAssets();
        $this->loadHooks();
    }

    private function getLinkManager() {
        if (!isset($this->linkManager)) {
            $this->linkManager = new LinkManager();
        }

        return $this->linkManager;
    }

    private function getLanguageManager() {
        if (!isset($this->languageManager)) {
            $this->languageManager = new LanguageManager();
        }

        return $this->languageManager->getLanguageManager();
    }
}

$onSave = new OnSave();
$onSave->init();