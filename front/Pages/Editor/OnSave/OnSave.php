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
    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
        $this->clientSeoSansMigraine = new Client();
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "OnSave.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "OnSave.css", [], TSM__VERSION);
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
        add_action("wp_ajax_traduire-sans-migraine_editor_start_translate", [$this, "startTranslate"]);
        add_action("wp_ajax_traduire-sans-migraine_editor_get_state_translate", [$this, "getTranslateState"]);
        add_action("wp_ajax_traduire-sans-migraine_editor_get_post_translated", [$this, "getTranslatedPostId"]);
    }

    public function startTranslate() {
        if (!isset($_GET["post_id"]) || !isset($_GET["language"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post ID missing")]);
            wp_die();
        }
        $result = $this->clientSeoSansMigraine->checkCredential("BFAZIOEZ29828ED128");
        if (!$result) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token invalid")]);
            wp_die();
        }
        $postId = $_GET["post_id"];
        $codeTo = $_GET["language"];
        $post = get_post($postId);
        if (!$post) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post not found")]);
            wp_die();
        }
        $languageManager = new LanguageManager();
        $codeFrom = $languageManager->getLanguageManager()->getLanguageForPost($postId);
        $dataToTranslate = [];

        if (!empty($post->post_content)) { $dataToTranslate["content"] = $post->post_content; }
        if (!empty($post->post_title)) { $dataToTranslate["title"] = $post->post_title; }
        if (!empty($post->post_excerpt)) { $dataToTranslate["excerpt"] = $post->post_excerpt; }
        if (!empty($post->post_name)) { $dataToTranslate["slug"] = $post->post_name; }
        if (is_plugin_active("yoast-seo-premium/yoast-seo-premium.php")) {
            $dataToTranslate["metaTitle"] = get_post_meta($postId, "_yoast_wpseo_title", true);
            $dataToTranslate["metaDescription"] = get_post_meta($postId, "_yoast_wpseo_metadesc", true);
            $dataToTranslate["metaKeywords"] = get_post_meta($postId, "_yoast_wpseo_metakeywords", true);
        }
        if (is_plugin_active("seo-by-rank-math/rank-math.php")) {
            $dataToTranslate["rankMathDescription"] = get_post_meta($postId, "rank_math_description", true);
            $dataToTranslate["rankMathTitle"] = get_post_meta($postId, "rank_math_title", true);
            $dataToTranslate["rankMathFocusKeyword"] = get_post_meta($postId, "rank_math_focus_keyword", true);
        }



        $result = $this->clientSeoSansMigraine->startTranslation($dataToTranslate, $codeFrom, $codeTo);
        if ($result["success"]) {
            $tokenId = $result["data"]["tokenId"];
            update_option("_seo_sans_migraine_state_" . $tokenId, [
                "percentage" => 50,
                "status" => Step::$STEP_STATE["PROGRESS"],
                "html" => TextDomain::__("The otters are translating your post 🦦"),
            ]);
            update_option("_seo_sans_migraine_postId_" . $tokenId, $postId);
        }
        echo json_encode($result);
        wp_die();
    }

    public function getTranslateState() {
        if (!isset($_GET["tokenId"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token ID missing")]);
            wp_die();
        }
        $tokenId = $_GET["tokenId"];
        $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
            "percentage" => 25,
            "status" => Step::$STEP_STATE["PROGRESS"],
            "html" => TextDomain::__("We will create and translate your post 💡"),
        ]);
        if (!$state) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Token not found")]);
            wp_die();
        }
        if (isset($state[4]) && $state[4] === "success") {
            delete_option("_seo_sans_migraine_state_" . $tokenId);
            delete_option("_seo_sans_migraine_postId_" . $tokenId);
        }
        echo json_encode(["success" => true, "data" => $state]);
        wp_die();
    }

    public function getTranslatedPostId() {
        if (!isset($_GET["post_id"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Post ID missing")]);
            wp_die();
        }
        if (!isset($_GET["language"])) {
            echo json_encode(["success" => false, "error" => TextDomain::__("Language missing")]);
            wp_die();
        }
        $languageManager = new LanguageManager();
        $postId = $_GET["post_id"];
        $language = $_GET["language"];
        $translatedPostId = $languageManager->getLanguageManager()->getTranslationPost($postId, $language);
        echo json_encode(["success" => true, "data" => $translatedPostId]);
        wp_die();
    }

    public function render() {
        if (!isset($_GET["post_id"])) {
            Modal::render(TSM__NAME, Alert::getHTML(TSM__NAME, TextDomain::__("Post ID is not set"), "danger"));
            return;
        }
        $localPostId = $_GET["post_id"];
        $languageManager = new LanguageManager();
        $linkManager = new LinkManager();
        $postContent = get_post($localPostId)->post_content;
        $codeFrom = $languageManager->getLanguageManager()->getLanguageForPost($localPostId);
        $listsUrlsIssues = $linkManager->extractAndRetrieveInternalLinks($postContent, $codeFrom, true);
        ob_start();
        ?>
        <div class="traduire-sans-migraine-list-languages">
        <?php
            try {
                $translationsPosts = $languageManager->getLanguageManager()->getAllTranslationsPost($localPostId);
                $enrichedTranslationsPosts = [];
                foreach ($translationsPosts as $codeSlug => $translationPost) {
                    $name = $translationPost["name"];
                    $flag = $translationPost["flag"];
                    $code = $translationPost["code"];
                    $postId = $translationPost["postId"];
                    $checked = !$postId && $postId != $localPostId;

                    $issuesTranslatedUrls = $linkManager->getIssuedInternalLinks($postContent, $codeFrom, $code);
                    $notTranslated = $issuesTranslatedUrls["notTranslated"];
                    $notPublished = $issuesTranslatedUrls["notPublished"];
                    $haveWarnings = count($notTranslated) + count($notPublished) > 0;

                    $enrichedTranslationsPosts[$codeSlug] = [
                        "name" => $name,
                        "flag" => $flag,
                        "code" => $code,
                        "postId" => $postId,
                        "checked" => $checked,
                        "haveWarnings" => $haveWarnings,
                        "notTranslated" => $notTranslated,
                        "notPublished" => $notPublished
                    ];
                }

                usort($enrichedTranslationsPosts, function ($a, $b) use ($localPostId) {
                    if ($a["postId"] == $localPostId)
                        return -1;
                    if ($b["postId"] == $localPostId)
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
                foreach ($enrichedTranslationsPosts as $codeSlug => $enrichedTranslationPost) {
                    $name = $enrichedTranslationPost["name"];
                    $flag = $enrichedTranslationPost["flag"];
                    $code = $enrichedTranslationPost["code"];
                    $postId = $enrichedTranslationPost["postId"];
                    $checked = $enrichedTranslationPost["checked"];
                    $notTranslated = $enrichedTranslationPost["notTranslated"];
                    $notPublished = $enrichedTranslationPost["notPublished"];
                    $haveWarnings = $enrichedTranslationPost["haveWarnings"];
                    ?>
                    <div class="language" data-language="<?php echo $code; ?>">
                        <div class="left-column">
                            <?php Checkbox::render($flag . " " . $name, $code, $checked, $postId == $localPostId); ?>
                        </div>
                        <div class="right-column">
                            <?php
                            if ($postId == $localPostId) {
                                Alert::render(false, TextDomain::__("We will use the saved content of your post in %s as a source to translate in others language.", $name), "success", [
                                    "isDismissible" => false
                                ]);
                                if (count($listsUrlsIssues) > 0) {
                                    $listHTML = "<ul>";
                                    foreach ($listsUrlsIssues as $url => $state) {
                                        $listHTML .= "<li>".$url."</li>";
                                    }
                                    $listHTML .= "</ul>";
                                    Tooltip::render(
                                    "<span class='warning-issues'>" . TextDomain::_n("🔎 We found %s issue", "🔎 We found %s issue", count($listsUrlsIssues), count($listsUrlsIssues))  . "</span>",
                                    Alert::getHTML(TextDomain::__("We found some issues"), TextDomain::__("The followings articles will not be translated cause we could not find them : %s", $listHTML), "warning", [
                                        "isDismissible" => false
                                    ]));
                                }
                            } else {
                                $indicatorText = TextDomain::__("We are impatient to help you with your translations! Just click the translate button.");
                                if ($haveWarnings) {
                                    $listHTML = "<ul>";
                                    if (count($notTranslated) > 0) {
                                        foreach ($notTranslated as $url => $postId) {
                                            $listHTML .= "<li>" . $url . "</li>";
                                        }
                                    }
                                    if (count($notPublished) > 0) {
                                        foreach ($notPublished as $url => $postId) {
                                            $listHTML .= "<li>" . $url . "</li>";
                                        }
                                    }
                                    $listHTML .= "</ul>";
                                    $indicatorText .= "<br/>" . Tooltip::getHTML(
                              "<span class='warning-issues'>" . TextDomain::_n("🔎 We found %s issue", "🔎 We found %s issues", count($notPublished) + count($notTranslated), count($notPublished) + count($notTranslated))  . "</span>",
                                        Alert::getHTML(TextDomain::__("We found some issues"), TextDomain::__("The links will not be translated cause they are either not published or not available : %s", $listHTML), "warning", [
                                            "isDismissible" => false
                                        ]));
                                }
                                Step::render([
                                    "classname" => $checked ? "":  "hidden",
                                    "indicatorText" => $indicatorText
                                ]);
                                if ($checked) {
                                    Alert::render(false, TextDomain::__("Use the checkbox on the left to add this language to the list of translations, it will create a new article in %s", $name), "primary", [
                                        "isDismissible" => false,
                                        "classname" => $checked ? "hidden" : ""
                                    ]);
                                } else {
                                    Alert::render(false, TextDomain::__("Use the checkbox on the left to add this language to the list of translations. It will overwrite the current translation in %s", $name), "primary", [
                                        "isDismissible" => false,
                                        "classname" => $checked ? "hidden" : ""
                                    ]);
                                }
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
                . TextDomain::__("Go to %s to add a new language.", $languageManager->getLanguageManager()->getLanguageManagerName())
                . "<br/>"
                . TextDomain::__("Then come back here to translate your post."),
                "<img width='72' src='https://www.seo-sans-migraine.fr/wp-content/uploads/2024/01/loutre_ampoule.png' alt='loutre_ampoule' />");
        $htmlContent = ob_get_clean();
        Modal::render(TSM__NAME, $htmlContent, [
            Button::getHTML(TextDomain::__("Translate now"), "success", "translate-button"),
            Button::getHTML(TextDomain::__("Translate later"), "ghost", "closing-button"),
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
}

$onSave = new OnSave();
$onSave->init();