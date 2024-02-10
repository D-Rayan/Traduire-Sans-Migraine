<?php

namespace TraduireSansMigraine\Front\Pages\Editor\OnSave;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Modal;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
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

        $result = $this->clientSeoSansMigraine->startTranslation($dataToTranslate, $codeFrom, $codeTo);
        if ($result["success"]) {
            $tokenId = $result["data"]["tokenId"];
            update_option("_seo_sans_migraine_state_" . $tokenId, [
                "percentage" => 50,
                "status" => Step::$STEP_STATE["PROGRESS"],
                "html" => TextDomain::__("The otters works on your SEO optimization 🦦"),
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
        ob_start();
        ?>
        <p>
            <?php echo TextDomain::__("You don't see the language you're looking for?") ?>
        </p>
        <div class="traduire-sans-migraine-list-languages">
        <?php
            try {
                $translationsPosts = $languageManager->getLanguageManager()->getAllTranslationsPost($localPostId);
                usort($translationsPosts, function ($a, $b) use ($localPostId) {
                    if ($a["postId"] == $localPostId)
                        return -1;
                    if ($b["postId"] == $localPostId)
                        return 1;

                   return $a["name"] > $b["name"] ? 1 : -1;
                });
                foreach ($translationsPosts as $codeSlug => $translationPost) {
                    $name = $translationPost["name"];
                    $flag = $translationPost["flag"];
                    $code = $translationPost["code"];
                    $postId = $translationPost["postId"];
                    $checked = !$postId && $postId != $localPostId;
                    ?>
                    <div class="language" data-language="<?php echo $code; ?>">
                        <div class="left-column">
                            <?php Checkbox::render($flag . " " . $name, $code, $checked, $postId == $localPostId); ?>
                        </div>
                        <div class="right-column">
                            <?php
                            if ($postId == $localPostId) {
                                Alert::render(false, TextDomain::__("%s will use this language as reference.", TSM__NAME), "success", [
                                        "isDismissible" => false
                                ]);
                            } else {
                                Step::render([
                                    "classname" => $checked ? "":  "hidden"
                                ]);
                                Alert::render(false, TextDomain::__("Use the checkbox on the left to add this language to the list of translations"), "primary", [
                                    "isDismissible" => false,
                                    "classname" => $checked ? "hidden" : ""
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