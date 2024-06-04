<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Bulk;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
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
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Bulk.css", [], TSM__VERSION);
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
        wp_send_json_success();
    }

    public function removeItemFromQueue() {
        $queue = Queue::getInstance();
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguages();
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
        <span class="second-color"><?php echo TextDomain::__("Votre contenu international à portée de main 💊"); ?></span>
        <?php
        return ob_get_clean();
    }

    public function displayQueue() {
        $page = $_GET["page"] ?? 1;
        self::renderQueueProgress($page);
        wp_die();
    }

    private static function renderQueueProgress($page) {
        echo self::getHTMLQueueProgress($page);
    }

    private static function getHTMLQueueProgress($page) {
        ob_start();
        $page = intval($page);
        $Queue = Queue::getInstance();
        $queue = $Queue->getQueue();
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguages();
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
                <div class="bulk-queue-title">
                    <span>
                        <?php
                            if ($total > $translatedDone) {
                                echo TextDomain::_n("%s traduction est en cours", "%s traductions sont en cours", count($queue), count($queue));
                            } else {
                                echo TextDomain::_n("La traduction est terminée", "%s traductions sont terminées", count($queue), count($queue));
                            }
                        ?>
                    </span>
                    <div class="actions-queue">
                        <?php
                            Tooltip::render(
                                    '<span data-action="play-queue" class="icon icon-play ' . (($statusQueue === "processing" || $translatedDone === $total) ? "disable" : "") . '"><svg viewBox="64 64 896 896" focusable="false" data-icon="play-circle" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M512 64C264.6 64 64 264.6 64 512s200.6 448 448 448 448-200.6 448-448S759.4 64 512 64zm0 820c-205.4 0-372-166.6-372-372s166.6-372 372-372 372 166.6 372 372-166.6 372-372 372z"></path><path d="M719.4 499.1l-296.1-215A15.9 15.9 0 00398 297v430c0 13.1 14.8 20.5 25.3 12.9l296.1-215a15.9 15.9 0 000-25.8zm-257.6 134V390.9L628.5 512 461.8 633.1z"></path></svg></span>',
                                TextDomain::__("Restart the Queue"));
                        ?>
                        <?php
                            Tooltip::render(
                                    '<span data-action="pause-queue" class="icon icon-pause ' . (($statusQueue === "idle") ? "disable" : "") . '"><svg viewBox="64 64 896 896" focusable="false" data-icon="pause" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M304 176h80v672h-80zm408 0h-64c-4.4 0-8 3.6-8 8v656c0 4.4 3.6 8 8 8h64c4.4 0 8-3.6 8-8V184c0-4.4-3.6-8-8-8z"></path></svg></span>',
                                TextDomain::__("Pause the Queue"));
                        ?>
                        <?php
                            Tooltip::render(
                                    '<span data-action="delete-queue" class="icon icon-delete"><svg fill-rule="evenodd" viewBox="64 64 896 896" focusable="false" data-icon="close" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M799.86 166.31c.02 0 .04.02.08.06l57.69 57.7c.04.03.05.05.06.08a.12.12 0 010 .06c0 .03-.02.05-.06.09L569.93 512l287.7 287.7c.04.04.05.06.06.09a.12.12 0 010 .07c0 .02-.02.04-.06.08l-57.7 57.69c-.03.04-.05.05-.07.06a.12.12 0 01-.07 0c-.03 0-.05-.02-.09-.06L512 569.93l-287.7 287.7c-.04.04-.06.05-.09.06a.12.12 0 01-.07 0c-.02 0-.04-.02-.08-.06l-57.69-57.7c-.04-.03-.05-.05-.06-.07a.12.12 0 010-.07c0-.03.02-.05.06-.09L454.07 512l-287.7-287.7c-.04-.04-.05-.06-.06-.09a.12.12 0 010-.07c0-.02.02-.04.06-.08l57.7-57.69c.03-.04.05-.05.07-.06a.12.12 0 01.07 0c.03 0 .05.02.09.06L512 454.07l287.7-287.7c.04-.04.06-.05.09-.06a.12.12 0 01.07 0z"></path></svg></span>',
                                TextDomain::__("Delete the Queue"));
                        ?>
                    </div>
                </div>
                <?php if ($translatedDone < $total) { ?>
                <div class="bulk-queue-description"><?php echo TextDomain::__("Vous n'avez rien à faire, tout se déroule en arrière plan. Vous pouvez voir l'avancé de la traduction du contenu en cours ci-dessous."); ?></div>
                <?php } ?>
                <?php
                Step::render([
                    "percentage" => $translatedDone / $total * 100,
                    "status" => $haveError ? Step::$STEP_STATE["ERROR"] : ($translatedDone === $total ? Step::$STEP_STATE["DONE"] : Step::$STEP_STATE["PROGRESS"]),
                    "indicatorText" => Button::getHTML(TextDomain::__("See the translations state"), "primary", "traduire-sans-migraine-bulk-queue-display"),
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
                            }
                        } else if ($isProcessed) {
                            $state["status"] = Step::$STEP_STATE["DONE"];
                            $state["percentage"] = 100;
                            if (isset($data["message"])) {
                                $state["message"]["id"] = TextDomain::__($data["message"]);
                            }
                        } else if ($onGoing) {
                            $state = get_option("_seo_sans_migraine_state_" . $data["tokenId"], $state);
                        }

                        ?>
                        <div class="bulk-queue-item">
                            <div class="bulk-queue-item-post">
                                <?php if (!isset($item["processed"])) { ?>
                                <div class="actions-queue">
                                    <?php
                                    Tooltip::render(
                                        '<span class="icon icon-delete" data-post-id="'.$postId.'" data-action="remove-from-queue"><svg viewBox="64 64 896 896" focusable="false" data-icon="delete" width="1em" height="1em" fill="currentColor" aria-hidden="true"><path d="M360 184h-8c4.4 0 8-3.6 8-8v8h304v-8c0 4.4 3.6 8 8 8h-8v72h72v-80c0-35.3-28.7-64-64-64H352c-35.3 0-64 28.7-64 64v80h72v-72zm504 72H160c-17.7 0-32 14.3-32 32v32c0 4.4 3.6 8 8 8h60.4l24.7 523c1.6 34.1 29.8 61 63.9 61h454c34.2 0 62.3-26.8 63.9-61l24.7-523H888c4.4 0 8-3.6 8-8v-32c0-17.7-14.3-32-32-32zM731.3 840H292.7l-24.2-512h487l-24.2 512z"></path></svg></span>',
                                        TextDomain::__("Remove from the Queue"));
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
        return ob_get_clean();
    }

    private static function getContent() {
        global $wpdb;
        ob_start();
        $languageManager = new LanguageManager();
        $defaultLanguage = $languageManager->getLanguageManager()->getDefaultLanguage();
        ?>
        <div class="bulk-content">
        <?php
        if ($defaultLanguage === false) {
            Alert::render("error", "No default language found. Please check your configuration.", "error");
            ?>
            </div>
            <?php
            return ob_get_clean();
        }

        $languagesTranslatable = [];
        $languagesAvailable = [];
        foreach ($languageManager->getLanguageManager()->getLanguages() as $language) {
            $languagesAvailable[$language["code"]] = $language;
            $languagesTranslatable[$language["code"]] = $language;
        }

        if (count($languagesTranslatable) < 2) {
            Alert::render("error", "You need at least two languages to use this feature.", "error");
            ?>
            </div>
            <?php
            return ob_get_clean();
        }


        $authors = get_users();
        $selectedLanguageFrom = isset($_GET["languageFrom"]) && isset($languagesAvailable[$_GET["languageFrom"]]) ? $_GET["languageFrom"] : $defaultLanguage["code"];
        $selectedLanguageTo = isset($_GET["languageTo"]) && isset($languagesTranslatable[$_GET["languageTo"]]) ? $_GET["languageTo"] : array_key_first($languagesTranslatable);
        if ($selectedLanguageTo === $selectedLanguageFrom) {
            $selectedLanguageTo = array_key_last($languagesTranslatable);
            if ($selectedLanguageFrom === $selectedLanguageTo) {
                $selectedLanguageTo = array_key_first($languagesTranslatable);
            }
        }
        $queryFetchPosts = $wpdb->prepare(
                "SELECT * FROM $wpdb->posts 
                        LEFT JOIN $wpdb->term_relationships trFrom ON ID = trFrom.object_id 
                        WHERE 
                            post_type IN ('post', 'page') AND 
                            post_status!='trash' AND 
                            trFrom.term_taxonomy_id = %d AND
                            (
                                (SELECT COUNT(*) FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                    trTaxonomyTo.taxonomy = 'post_translations' AND 
                                    trTaxonomyTo.description LIKE '%%\"%s\"%%' AND 
                                    trTaxonomyTo.term_taxonomy_id IN (
                                        SELECT trTo.term_taxonomy_id FROM wp_term_relationships trTo WHERE trTo.object_id = ID
                                    )
                                ) = 0
                            )
                        ORDER BY post_status DESC, ID DESC
                        ",
                $languagesAvailable[$selectedLanguageFrom]["id"],
                $selectedLanguageTo
        );

        $posts = $wpdb->get_results($queryFetchPosts);

        ?>
        <div id="queue-container">
        <?php
            self::renderQueueProgress(1);
        ?>
        </div>
        <div class="actions">
            <form method="get">
                <input type="hidden" name="page" id="page" value="<?php echo $_GET["page"]; ?>"/>
                <label for="languageFrom">Afficher les contenus en</label>
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
                <label for="languageTo">qui ne sont pas encore traduit en</label>
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
                <?php
                    Button::render(TextDomain::__("Rechercher"), "ghost", "traduire-sans-migraine-bulk-filter");
                ?>
            </form>
            <?php
                Button::render("", "primary", "traduire-sans-migraine-bulk-translate", [
                    "default-plural" => TextDomain::__("Traduire les %var% contenus sélectionnés"),
                    "default-singular" => TextDomain::__("Traduire le contenu sélectionné"),
                    "default-none" => TextDomain::__("Sélectionner au moins un contenu à traduire"),
                ]);
            ?>
        </div>
        <table class="traduire-sans-migraine-table">
            <thead>
                <tr>
                    <th><?php Checkbox::render("", "all-posts"); ?></th>
                    <th>Titre</th>
                    <th>Auteur/Autrice</th>
                    <th>Etat</th>
                </tr>
            </thead>
            <tbody>
            <?php
            $Queue = Queue::getInstance();
            foreach ($posts as $post) {
                if ($Queue->isFromQueue($post->ID)) {
                    continue;
                }
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
        </div>
        <?php
        return ob_get_clean();
    }

    private static function displayAuthorName($authors, $authorId) {
        foreach ($authors as $author) {
            if ($author->data->ID === $authorId) {
                echo $author->display_name;
                return;
            }
        }
        echo "Inconnu (ID: $authorId)";
    }

    private static function getDescription() {
        ob_start();
        ?>
        <span><?php echo TextDomain::__("Envie de passer à la vitesse supérieure ? Traduisez tout vos contenus en un seul clic."); ?></span>
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