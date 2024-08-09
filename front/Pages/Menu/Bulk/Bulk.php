<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Bulk;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Icon;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Components\Tooltip;use TraduireSansMigraine\Front\Pages\Menu\Menu;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;

class Bulk {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Bulk.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Bulk.min.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        if (!isset($_GET["page"]) || $_GET["page"] !== "traduire-sans-migraine-bulk") {
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

    }

    public function loadAdminHooks() {
        add_action("wp_ajax_traduire-sans-migraine_add_items", [$this, "addItemsToQueue"]);
        add_action("wp_ajax_traduire-sans-migraine_display_queue", [$this, "displayQueue"]);
        add_action("wp_ajax_traduire-sans-migraine_remove_item", [$this, "removeItemFromQueue"]);
        add_action("wp_ajax_traduire-sans-migraine_restart_queue", [$this, "restartQueue"]);
        add_action("wp_ajax_traduire-sans-migraine_pause_queue", [$this, "pauseQueue"]);
        add_action("wp_ajax_traduire-sans-migraine_delete_queue", [$this, "deleteQueue"]);
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    public function addItemsToQueue() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine-bulk-add-items")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        $postIds = $_POST["ids"];
        $languageTo = $_POST["languageTo"];
        $items = [];
        foreach ($postIds as $postId) {
            $items[] = [
                "ID" => $postId,
                "languageTo" => $languageTo,
            ];
        }
        $queue->add($items);
        wp_send_json_success(["wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-display-queue")]);
    }

    public function removeItemFromQueue() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine-bulk-remove-item")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguagesActives();
        $flagsMap = [];
        foreach ($languages as $language) {
            $flagsMap[$language["code"]] = $language["flag"];
        }
        $postId = $_GET["postId"];
        $item = $queue->getFromQueue($postId);
        if ($item && !isset($item["processed"]) && !isset($item["error"]) && !isset($item["data"]["tokenId"])) {
            $queue->remove(["ID" => $postId]);
            wp_send_json_success([
                "title" => TextDomain::__("The item has been removed from the queue"),
                "message" => TextDomain::__("%s will not be translated into %s", get_the_title($postId), $flagsMap[$item["languageTo"]]),
                "logo" => "loutre_docteur_no_shadow.png"
            ]);
        } else {
            wp_send_json_error([
                "title" => TextDomain::__("The item could not be removed from the queue"),
                "message" => TextDomain::__("The item is currently being processed or has already been processed."),
                "logo" => "loutre_triste.png"
            ]);
        }
        wp_die();
    }

    public function restartQueue() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine-bulk-restart-queue")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        if ($queue->getState() === "idle" && $queue->getNextItem() !== null) {
            $queue->startNextProcess();
            wp_send_json_success([
                "title" => TextDomain::__("The queue has been restarted"),
                "message" => TextDomain::__("The next item in the queue has been started."),
                "logo" => "loutre_docteur_no_shadow.png"
            ]);
        } else {
            wp_send_json_error([
                "title" => TextDomain::__("The queue could not be restarted"),
                "message" => TextDomain::__("The queue is currently processing or is empty."),
                "logo" => "loutre_triste.png"
            ]);
        }
        wp_die();
    }

    public function pauseQueue() {
        if (!isset($_POST["wp_nonce"])  || !wp_verify_nonce($_POST["wp_nonce"], "traduire-sans-migraine-bulk-pause-queue")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        $queue->stopQueue();
        wp_send_json_success([
            "title" => TextDomain::__("The queue has been paused"),
            "message" => TextDomain::__("The queue is no longer processing."),
            "logo" => "loutre_docteur_no_shadow.png"
        ]);
        wp_die();
    }

    public function deleteQueue() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine-bulk-delete-queue")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $queue = Queue::getInstance();
        $queue->deleteQueue();
        wp_send_json_success([
            "title" => TextDomain::__("The queue has been deleted"),
            "message" => TextDomain::__("All the translations in the queue have been removed."),
            "logo" => "loutre_docteur_no_shadow.png"
        ]);
        wp_die();
    }

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TSM__NAME; ?></span>
        <span class="second-color"><?php echo TextDomain::__("Translate all your content in few seconds 💊"); ?></span>
        <?php
        return ob_get_clean();
    }

    public function displayQueue() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine-bulk-display-queue")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $page = $_GET["page"] ?? 1;
        self::renderQueueProgress($page);
        wp_die();
    }

    private static function renderQueueProgress($page) {
        $page = intval($page);
        $Queue = Queue::getInstance();
        $queue = $Queue->getQueue();
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguagesActives();
        $flagsMap = [];
        foreach ($languages as $language) {
            $flagsMap[$language["code"]] = $language["flag"];
        }
        if (!empty($queue)) {
            $total = count($queue);
            $translatedDone = 0;
            $haveError = false;
            foreach ($queue as $item) {
                if (isset($item["processed"])) {
                    $translatedDone++;
                }
                if (!$haveError && isset($item["error"])) {
                    $haveError = true;
                }
            }
            $statusQueue = $Queue->getState();
            ?>
            <div class="bulk-queue">
                <input type="hidden" id="wp_nonce_display_queue" name="wp_nonce_display_queue" value="<?php echo wp_create_nonce("traduire-sans-migraine-bulk-display-queue"); ?>"/>
                <div class="bulk-queue-title">
                    <span>
                        <?php
                            if ($total > $translatedDone) {
                                echo TextDomain::_n("%s translation in progress", "%s translations in progress", count($queue), count($queue));
                            } else {
                                echo TextDomain::_n("The translation is done", "%s translations are done", count($queue), count($queue));
                            }
                        ?>
                    </span>
                    <div class="actions-queue">
                        <?php
                            Tooltip::render(
                                Icon::getHTML("play", "#4caf50", ($statusQueue === "processing" || $translatedDone === $total), ["action" => "play-queue", "wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-restart-queue")]),
                                TextDomain::__("Restart the Queue"),
                                [
                                    "padding" => true,
                                ]
                            );
                        ?>
                        <?php
                            Tooltip::render(
                                Icon::getHTML("pause", "#ff9800", ($statusQueue === "idle"), ["action" => "pause-queue", "wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-pause-queue")]),
                                TextDomain::__("Pause the Queue"),
                                [
                                    "padding" => true,
                                ]
                            );
                        ?>
                        <?php
                            Tooltip::render(
                                Icon::getHTML("close", "#f44336", false, ["action" => "delete-queue", "wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-delete-queue")]),
                                TextDomain::__("Delete the Queue"),
                                [
                                    "padding" => true,
                                ]
                            );
                        ?>
                    </div>
                </div>
                <?php if ($translatedDone < $total) { ?>
                <div class="bulk-queue-description"><?php echo TextDomain::__("You don't have to do anything, it's all in background. You can check the progress just below."); ?></div>
                <?php } ?>
                <?php
                Step::render([
                    "percentage" => $translatedDone / $total * 100,
                    "status" => $haveError ? Step::$STEP_STATE["ERROR"] : ($translatedDone === $total ? Step::$STEP_STATE["DONE"] : Step::$STEP_STATE["PROGRESS"]),
                    "indicatorText" => Button::getHTML(TextDomain::__("See the translations progression"), "primary", "traduire-sans-migraine-bulk-queue-display"),
                ]);
                ?>
                <div class="bulk-queue-items">
                    <?php
                    $itemsPerPage = 10;
                    $totalPages = max(ceil(count($queue) / $itemsPerPage), 1);
                    $queue = array_slice($queue, ($page - 1) * $itemsPerPage, $itemsPerPage);
                    foreach ($queue as $item) {
                        $postId = $item["ID"];
                        $isProcessed = isset($item["processed"]);
                        $data = $item["data"] ?? [];
                        $onGoing = isset($data["tokenId"]);
                        $error = $item["error"] ?? false;
                        $state = [
                            "percentage" => 0,
                            "status" => Step::$STEP_STATE["PROGRESS"],
                            "message" => [
                                "id" => TextDomain::__("Waiting for the translation to start 🕐"),
                                "args" => []
                            ]
                        ];

                        if ($error) {
                            $state["status"] = Step::$STEP_STATE["ERROR"];
                            $state["percentage"] = 100;
                            if (isset($data["message"])) {
                                $state["message"]["id"] = TextDomain::__($data["message"]);
                                if (isset($data["buttons"])) {
                                    $state["buttons"] = [];
                                    foreach ($data["buttons"] as $button) {
                                        $state["buttons"][] = [
                                            "label" => TextDomain::__($button["label"]),
                                            "type" => $button["type"],
                                            "url" => $button["url"],
                                        ];
                                    }
                                }
                            }
                        } else if ($isProcessed) {
                            $state["status"] = Step::$STEP_STATE["DONE"];
                            $state["percentage"] = 100;
                            if (isset($data["message"])) {
                                $state["message"]["id"] = TextDomain::__($data["message"]);
                            } else if (isset($data["tokenId"])) {
                                $state = get_option("_seo_sans_migraine_state_" . $data["tokenId"], $state);
                            }
                        } else if ($onGoing) {
                            $state = get_option("_seo_sans_migraine_state_" . $data["tokenId"], $state);
                        }

                        ?>
                        <div class="bulk-queue-item">
                            <div class="bulk-queue-item-post">
                                <?php if (!isset($item["processed"]) && !$onGoing) { ?>
                                <div class="actions-queue">
                                    <?php
                                    Tooltip::render(
                                        Icon::getHTML("delete", "#f44336", false, ["postId" => $postId, "action" => "remove-from-queue", "wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-remove-item")]),
                                        TextDomain::__("Remove from the Queue"),
                                        [
                                            "padding" => true,
                                        ]
                                    );
                                    ?>
                                </div>
                                <?php } ?>
                                <?php echo get_the_title($postId); ?>
                            </div>
                            <div class="bulk-queue-item-language"><?php echo $flagsMap[$item["languageTo"]]; ?></div>
                            <div class="bulk-queue-item-state">
                                <?php
                                    if (isset($state["message"])) {
                                        $state["indicatorText"] = TextDomain::__($state["message"]["id"], ...$state["message"]["args"]);
                                    }
                                    Step::render($state);
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
                    <div class="bulk-queue-pagination">
                        <?php
                        if ($totalPages > 1) {
                            $minimumPage = max(1, $page - 2);
                            $maximumPage = min($totalPages, $page + 2);
                            if ($minimumPage >= 2) {
                                ?>
                                <span class="bulk-queue-pagination-item" data-page="1">1</span>
                                <?php if ($minimumPage > 2) { ?> <span class="bulk-queue-pagination-item disable">...</span><?php } ?>
                                <?php
                            }
                            for ($i = $minimumPage; $i <= $maximumPage; $i++) {
                                ?>
                                <span class="bulk-queue-pagination-item <?php if ($i === $page) { echo "active"; } ?>" data-page="<?php echo $i; ?>"><?php echo $i; ?></span>
                                <?php
                            }
                            if ($maximumPage <= $totalPages - 1) {
                                ?>
                                <?php if ($maximumPage < $totalPages - 1) { ?> <span class="bulk-queue-pagination-item disable">...</span><?php } ?>
                                <span class="bulk-queue-pagination-item" data-page="<?php echo $totalPages; ?>"><?php echo $totalPages; ?></span>
                                <?php
                            }
                        }
                        ?>
                    </div>
                </div>
            </div>
            <?php
        }
    }

    private static function getPostTypes() {
        $postType = "all";
        if (isset($_GET["postType"]) && in_array($_GET["postType"], ["post", "page"])) {
            $postType = $_GET["postType"];
        }
        return $postType;
    }
    private static function getSelectedLanguages($languagesAvailable, $languagesTranslatable, $defaultLanguage) {
        $selectedLanguageFrom = isset($_GET["languageFrom"]) && isset($languagesAvailable[$_GET["languageFrom"]]) ? $_GET["languageFrom"] : $defaultLanguage["code"];
        $selectedLanguageTo = isset($_GET["languageTo"]) && isset($languagesTranslatable[$_GET["languageTo"]]) ? $_GET["languageTo"] : array_key_first($languagesTranslatable);
        if ($selectedLanguageTo === $selectedLanguageFrom) {
            $selectedLanguageTo = array_key_last($languagesTranslatable);
            if ($selectedLanguageFrom === $selectedLanguageTo) {
                $selectedLanguageTo = array_key_first($languagesTranslatable);
            }
        }
        return ["from" => $selectedLanguageFrom, "to" => $selectedLanguageTo];
    }

    private static function getPostsToDisplay($selectedLanguageFromId, $selectedLanguageToSlug, $postType = ["post", "page"]) {
        global $wpdb;
        $placeholders = str_repeat ('%s, ',  count ($postType) - 1) . '%s';

        $variables = array_merge([$selectedLanguageToSlug], $postType, [$selectedLanguageFromId]);
        $queryFetchPosts = $wpdb->prepare(
            "SELECT posts.ID, posts.post_title, posts.post_author, posts.post_status, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.description LIKE '%%\"%s\"%%' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM wp_term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap FROM $wpdb->posts posts
                        LEFT JOIN $wpdb->term_relationships trFrom ON ID = trFrom.object_id 
                        WHERE 
                            posts.post_type IN ($placeholders) AND 
                            posts.post_status!='trash' AND 
                            posts.post_status!='auto-draft' AND 
                            trFrom.term_taxonomy_id = %d AND 
                            (posts.post_title NOT LIKE '%Translation of post%' OR posts.post_content != 'This content is temporary... It will be either deleted or updated soon.' OR posts.post_status != 'draft')
                        ORDER BY posts.post_status DESC, posts.ID DESC
                        ",
            $variables
        );
        $posts = $wpdb->get_results($queryFetchPosts);
        $Queue = Queue::getInstance();
        $postsToDisplay = [];
        foreach ($posts as $post) {
            if ($Queue->isFromQueue($post->ID)) {
                continue;
            }
            $translationMap = !empty($post->translationMap) ? unserialize($post->translationMap) : [];
            if (isset($translationMap[$selectedLanguageToSlug]) && get_post_status($translationMap[$selectedLanguageToSlug]) !== "trash") {
                continue;
            }
            $postsToDisplay[] = $post;
        }
        return $postsToDisplay;
    }

    private static function getContent() {
        ob_start();
        $languageManager = new LanguageManager();
        $languagesTranslatable = [];
        $languagesAvailable = [];
        $defaultLanguage = $languageManager->getLanguageManager()->getDefaultLanguage();
        foreach ($languageManager->getLanguageManager()->getLanguagesActives() as $language) {
            $languagesAvailable[$language["code"]] = $language;
            $languagesTranslatable[$language["code"]] = $language;
        }
        if ($defaultLanguage === false) {
            ?>
            <div class="bulk-content">
            <?php
            Alert::render("error", "No default language found. Please check your configuration.", "error");
            ?>
            </div>
            <?php
            return ob_get_clean();
        }
        if (count($languagesTranslatable) < 2) {
            ?>
            <div class="bulk-content">
            <?php
            Alert::render("error", "You need at least two languages to use this feature.", "error");
            ?>
            </div>
            <?php
            return ob_get_clean();
        }

        $selectedLanguages = self::getSelectedLanguages($languagesAvailable, $languagesTranslatable, $defaultLanguage);
        $postType = self::getPostTypes();
        $selectedLanguageFrom = $selectedLanguages["from"];
        $selectedLanguageTo = $selectedLanguages["to"];
        $postTypes = $postType === "all" ? ["post", "page"] : [$postType];
        $postsToDisplay = self::getPostsToDisplay($languagesAvailable[$selectedLanguageFrom]["id"], $selectedLanguageTo, $postTypes);
        ?>
        <div class="bulk-content">
            <div id="queue-container">
            <?php
                self::renderQueueProgress(1);
            ?>
            </div>
            <div class="actions">
                <?php
                self::renderForm($languagesAvailable, $selectedLanguageFrom, $languagesTranslatable, $selectedLanguageTo, $postType);
                if (!empty($postsToDisplay)) {
                    Button::render("", "primary", "traduire-sans-migraine-bulk-translate", [
                        "default-plural" => TextDomain::__("Translate the %var% content selected"),
                        "default-singular" => TextDomain::__("Translate the content selected"),
                        "default-none" => TextDomain::__("Select at least one content to translate"),
                        "wp_nonce" => wp_create_nonce("traduire-sans-migraine-bulk-add-items")
                    ]);
                }
                ?>
            </div>
            <?php
                self::renderPosts($postsToDisplay);
            ?>
        </div>
        <?php
        return ob_get_clean();
    }

    private static function renderForm($languagesAvailable, $selectedLanguageFrom, $languagesTranslatable, $selectedLanguageTo, $postType) {
        ?>
        <form method="get">
            <input type="hidden" name="page" id="page" value="<?php echo $_GET["page"]; ?>"/>
            <label for="languageFrom"><?php echo TextDomain::__("Display the content in"); ?></label>
            <select name="languageFrom" id="languageFrom">
                <?php
                foreach ($languagesAvailable as $slug => $language) {
                    $name = $language["name"];
                    ?>
                    <option value="<?php echo $slug; ?>" <?php if ($selectedLanguageFrom === $slug) { echo "selected"; } ?>><?php echo $name; ?></option>
                    <?php
                }
                ?>
            </select>
            <label for="languageTo"><?php echo TextDomain::__("that are not translated in"); ?></label>
            <select name="languageTo" id="languageTo">
                <?php
                foreach ($languagesTranslatable as $slug => $language) {
                    $name = $language["name"];
                    ?>
                    <option value="<?php echo $slug; ?>" <?php if ($selectedLanguageTo === $slug) { echo "selected"; } ?>><?php echo $name; ?></option>
                    <?php
                }
                ?>
            </select>
            <input type="hidden" id="languageToHidden" name="languageToHidden" value="<?php echo $selectedLanguageTo; ?>"/>
            <label for="postType"><?php echo TextDomain::__("among the"); ?></label>
            <select name="postType" id="postType">
                <option value="all" <?php if ($postType === "all") { echo "selected"; } ?>><?php echo TextDomain::__("Pages and Posts"); ?></option>
                <option value="post" <?php if ($postType === "post") { echo "selected"; } ?>><?php echo TextDomain::__("Posts"); ?></option>
                <option value="page" <?php if ($postType === "page") { echo "selected"; } ?>><?php echo TextDomain::__("Pages"); ?></option>
            </select>
            <?php
            Button::render(TextDomain::__("Search"), "ghost", "traduire-sans-migraine-bulk-filter");
            ?>
        </form>
        <?php
    }

    private static function renderPosts($postsToDisplay) {
        if (empty($postsToDisplay)) {
            Alert::render(TextDomain::__("Oops!"), TextDomain::__("There is no content to translate with this search."), "warning", ["isDismissible" => false]);
            return;
        }
        $authors = get_users();
        ?>
        <table class="traduire-sans-migraine-table">
            <thead>
            <tr>
                <th><?php Checkbox::render("", "all-posts"); ?></th>
                <th><?php echo TextDomain::__("Title"); ?></th>
                <th><?php echo TextDomain::__("Author"); ?></th>
                <th><?php echo TextDomain::__("State"); ?></th>
            </tr>
            </thead>
            <tbody>
            <?php
            foreach ($postsToDisplay as $post) {
                ?>
                <tr>
                    <td><?php Checkbox::render("", "post-" . $post->ID); ?></td>
                    <td><?php echo $post->post_title; ?>(#<?php echo $post->ID; ?>)</td>
                    <td><?php self::displayAuthorName($authors, $post->post_author);  ?></td>
                    <td><?php echo $post->post_status; ?></td>
                </tr>
                <?php
            }
            ?>
            </tbody>
        </table>
        <?php
    }

    private static function displayAuthorName($authors, $authorId) {
        foreach ($authors as $author) {
            if ($author->data->ID === $authorId) {
                echo $author->display_name;
                return;
            }
        }
        echo TextDomain::__("Unknown (ID: %s)", $authorId);
    }

    private static function getDescription() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Want to go the the next step? Translate all your content in few seconds?"); ?></span>
        <?php
        return ob_get_clean();
    }
    static function render() {
        $content = self::getContent();
        $title = self::getTitle();
        $description = self::getDescription();
        Menu::render($title, $description, $content);
    }
}

$Bulk = new Bulk();
$Bulk->init();