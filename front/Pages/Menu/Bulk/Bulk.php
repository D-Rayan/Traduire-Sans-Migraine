<?php

namespace TraduireSansMigraine\Front\Pages\Menu\Bulk;
use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Front\Pages\Menu\Menu;
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

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TSM__NAME; ?></span>
        <span class="second-color"><?php echo TextDomain::__("Votre contenu international à portée de main 💊"); ?></span>
        <?php
        return ob_get_clean();
    }

    public function displayQueue() {
        self::renderQueueProgress();
        wp_die();
    }

    private static function renderQueueProgress() {
        echo self::getHTMLQueueProgress();
    }

    private static function getHTMLQueueProgress() {
        ob_start();
        $queue = Queue::getInstance()->getQueue();
        $languageManager = new LanguageManager();
        $languages = $languageManager->getLanguageManager()->getLanguages();
        $flagsMap = [];
        foreach ($languages as $language) {
            $flagsMap[$language["code"]] = $language["flag"];
        }
        if (!empty($queue)) {
            $translatedDone = 0;
            foreach ($queue as $item) {
                if (isset($item["processed"])) {
                    $translatedDone++;
                }
            }
            ?>
            <div class="bulk-queue">
                <div class="bulk-queue-title"><?php echo TextDomain::_n("%s traduction est en cours", "%s traductions sont en cours", count($queue), count($queue)); ?></div>
                <div class="bulk-queue-description"><?php echo TextDomain::__("Vous n'avez rien à faire, tout se déroule en arrière plan. Vous pouvez voir l'avancé de la traduction du contenu en cours ci-dessous."); ?></div>
                <?php
                Step::render([
                    "percentage" => $translatedDone / count($queue) * 100,
                    "classname" => "success",
                    "indicatorText" => Button::getHTML(TextDomain::__("Voir la file d'attente"), "primary", "traduire-sans-migraine-bulk-queue-display"),
                ]);
                ?>
                <div class="bulk-queue-items">
                    <?php
                    foreach ($queue as $index => $item) {
                        ?>
                        <div class="bulk-queue-item">
                            <div class="bulk-queue-item-post"><?php echo get_the_title($item["ID"]); ?></div>
                            <div class="bulk-queue-item-language"><?php echo $flagsMap[$item["languageTo"]]; ?></div>
                            <div class="bulk-queue-item-state">
                                <?php
                                    if (isset($item["processed"])) {
                                        if ($item["error"]) {
                                            $state = [
                                                "percentage" => 100,
                                                "status" => Step::$STEP_STATE["ERROR"],
                                                "message" => [
                                                    "id" => TextDomain::_f("Une erreur est survenue lors de la traduction de votre contenu 🚨"),
                                                    "args" => []
                                                ]
                                            ];
                                        } else {
                                            if (isset($item["data"]["tokenId"])) {
                                                $tokenId = $item["data"]["tokenId"];
                                                $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
                                                    "percentage" => 100,
                                                    "status" => Step::$STEP_STATE["DONE"],
                                                    "message" => [
                                                        "id" => TextDomain::_f("Votre contenu a été traduit avec succès 🎉"),
                                                        "args" => []
                                                    ]
                                                ]);
                                            } else {
                                                $state = [
                                                    "percentage" => 100,
                                                    "status" => Step::$STEP_STATE["DONE"],
                                                    "message" => [
                                                        "id" => TextDomain::_f("Votre contenu a été traduit avec succès 🎉"),
                                                        "args" => []
                                                    ]
                                                ];
                                            }
                                        }
                                    } else {
                                        if (isset($item["data"])) {
                                            if (isset($item["data"]["tokenId"])) {
                                                $tokenId = $item["data"]["tokenId"];
                                                $state = get_option("_seo_sans_migraine_state_" . $tokenId, [
                                                    "percentage" => 25,
                                                    "status" => Step::$STEP_STATE["PROGRESS"],
                                                    "message" => [
                                                        "id" => TextDomain::_f("We will create and translate your post 💡"),
                                                        "args" => []
                                                    ]
                                                ]);
                                            } else {
                                                $state = [
                                                    "percentage" => 25,
                                                    "status" => Step::$STEP_STATE["PROGRESS"],
                                                    "message" => [
                                                        "id" => TextDomain::_f("We will create and translate your post 💡"),
                                                        "args" => []
                                                    ]
                                                ];
                                            }
                                        } else {
                                            $state = [
                                                "percentage" => 0,
                                                "status" => Step::$STEP_STATE["PROGRESS"],
                                                "message" => [
                                                    "id" => TextDomain::_f("En attente"),
                                                    "args" => []
                                                ]
                                            ];
                                        }
                                    }
                                    if (isset($state["message"]) || $index === 3) {
                                        $state["indicatorText"] = TextDomain::__($state["message"]["id"], ...$state["message"]["args"]);
                                    }
                                    Step::render($state);
                                ?>
                            </div>
                        </div>
                        <?php
                    }
                    ?>
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
            self::renderQueueProgress();
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