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
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    private static function getTitle() {
        ob_start();
        ?>
        <span><?php echo TSM__NAME; ?></span>
        <span class="second-color"><?php echo TextDomain::__("Votre contenu international à portée de main 💊"); ?></span>
        <?php
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

        $queue = Queue::getInstance();
        $nextItem = $queue->getNextItem();
        if ($nextItem !== null) {
            ?>
            <div class="bulk-queue">
                <div class="bulk-queue-title"><?php echo TextDomain::_n("%s traduction est en cours", "%s des traductions sont en cours", 1, 1); ?></div>
                <div class="bulk-queue-description"><?php echo TextDomain::__("Vous n'avez rien à faire, tout se déroule en arrière plan. Vous pouvez voir l'avancé de la traduction du contenu en cours ci-dessous."); ?></div>
                <?php
                Step::render([
                    "percentage" => 50,
                    "classname" => "success",
                    "indicatorText" => TextDomain::__("Traduction en cours")
                ]);
                ?>
            </div>
            <?php
        }
        ?>
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
            foreach ($posts as $post) {
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