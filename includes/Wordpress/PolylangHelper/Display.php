<?php

namespace TraduireSansMigraine\Wordpress\PolylangHelper;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguageTerm;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationTerms;

class Display
{
    public function __construct()
    {
        add_action("tsm-enqueue-admin-scripts", [$this, "enqueueCSS"]);
        add_action("tsm-enqueue-admin-scripts", [$this, "enqueueJS"]);
        add_action("tsm-display-language-select", [$this, "displayFieldLanguage"], 10, 2);
        add_action("tsm-display-translations-table", [$this, "displayTranslations"], 10, 2);
    }

    public function init()
    {

    }

    public function enqueueCSS()
    {
        wp_enqueue_style('traduire_sans_migraine_admin_css', TSM__FRONT_PATH . 'css/form.min.css');
    }

    public function enqueueJS()
    {
        wp_enqueue_script('traduire_sans_migraine_admin_js', TSM__FRONT_PATH . 'js/form.min.js');
    }

    public function displayFieldLanguage($object, $isProduct)
    {
        global $tsm;

        $classLanguage = $isProduct ? LanguagePost::class : LanguageTerm::class;
        $objectId = null;
        if (!empty($object)) {
            $objectId = $isProduct ? $object->ID : $object->term_id;
        }
        $objectLanguage = empty($objectId) ? null : $classLanguage::getLanguage($objectId);
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        ?>
        <select id="traduire_sans_migraine_language" name="traduire_sans_migraine_language">
            <?php
            foreach ($languages as $language) {
                $matchObjectLanguage = $objectLanguage && $objectLanguage["id"] === $language["id"];
                $matchCurrentLanguage = !$objectLanguage && $currentLanguage === $language["code"];
                $matchDefaultLanguage = !$objectLanguage && !$currentLanguage && $language["default"];
                $isSelected = $matchObjectLanguage || $matchCurrentLanguage || $matchDefaultLanguage;
                ?>
                <option value="<?php echo $language["id"]; ?>" <?php if ($isSelected) {
                    echo "selected";
                } ?>><?php echo $language["name"]; ?></option>
                <?php
            }
            ?>
        </select>
        <?php
    }

    public function displayTranslations($object, $isProduct)
    {
        global $tsm;

        $classLanguage = $isProduct ? LanguagePost::class : LanguageTerm::class;
        $classTranslations = $isProduct ? TranslationPost::class : TranslationTerms::class;
        $objectId = null;
        if (!empty($object)) {
            $objectId = $isProduct ? $object->ID : $object->term_id;
        }

        if (!empty($objectId)) {
            $translations = $classTranslations::findTranslationFor($objectId);
        } else if (isset($_GET["related"])) {
            $translations = $classTranslations::findTranslationFor($_GET["related"]);
        } else {
            $translations = new $classTranslations();
        }
        $currentLanguage = $tsm->getPolylangManager()->getCurrentLanguageSlug();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $objectLanguage = empty($objectId) ? null : $classLanguage::getLanguage($objectId);
        $nonce = wp_create_nonce("traduire-sans-migraine");

        foreach ($languages as $language) {
            $translationId = $translations->getTranslation($language["code"]);

            $matchObjectLanguage = $objectLanguage && $objectLanguage["id"] === $language["id"];
            $matchCurrentLanguage = !$objectLanguage && $currentLanguage === $language["code"];
            $matchDefaultLanguage = !$objectLanguage && !$currentLanguage && $language["default"];
            $isHidden = $matchObjectLanguage || $matchCurrentLanguage || $matchDefaultLanguage;

            $class = ["row-translation"];
            if ($isHidden) {
                $class[] = "hidden";
            }
            $valueId = empty($translationId) ? "" : $translationId;
            $valueName = "";
            if (!empty($translationId)) {
                $valueName = $isProduct ? get_post_field("post_name", $translationId) : get_term_field("name", $translationId);
            }
            $languageId = $language["id"];
            ?>
            <div class="<?php echo implode(" ", $class); ?>"
                 data-language-id="<?php echo $language["id"]; ?>"
                 data-language-code="<?php echo $language["code"]; ?>"
                 data-nonce="<?php echo $nonce; ?>"
                 data-type="<?php echo $isProduct ? "products" : "categories"; ?>"
            >
                <?php echo $language["flag"]; ?>
                <input type="hidden"
                       name="traduire_sans_migraine_translations[<?php echo $languageId; ?>]"
                       value="<?php echo $valueId; ?>"
                >
                <input type="text"
                       name="traduire_sans_migraine_translations_display[<?php echo $languageId; ?>]"
                       value="<?php echo $valueName; ?>"
                >
                <?php
                if (!empty($translationId)) {
                    $taxonomy = $isProduct ? "" : get_term($translationId)->taxonomy;
                    $urlWoocommerceUpdateCategory = $isProduct ? get_edit_post_link($translationId) : get_edit_term_link($translationId, $taxonomy);
                    ?>
                    <a href="<?php echo $urlWoocommerceUpdateCategory; ?>"
                       target="_blank"
                    >
                        <span class="dashicons dashicons-edit-page"></span>
                    </a>
                    <?php
                } else if (!empty($objectId)) {
                    $taxonomy = $isProduct ? "" : get_term($objectId)->taxonomy;
                    $urlWoocommerceCreateCategory = $isProduct ? admin_url("post-new.php?post_type=product&lang=" . $language["code"] . "&related=" . $objectId) : admin_url("edit-tags.php?taxonomy=" . $taxonomy . "&post_type=product&lang=" . $language["code"] . "&related=" . $objectId);
                    ?>
                    <a href="<?php echo $urlWoocommerceCreateCategory; ?>"
                       target="_blank"
                    >
                        <span class="dashicons dashicons-plus-alt2"></span>
                    </a>
                    <?php
                }
                ?>
            </div>
            <?php
        }
    }
}

$Display = new Display();
$Display->init();
