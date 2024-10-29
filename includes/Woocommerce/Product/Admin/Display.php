<?php

namespace TraduireSansMigraine\Woocommerce\Product\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Languages\LanguagePost;
use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationPost;
use TraduireSansMigraine\Wordpress\TextDomain;

class Display
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        // register new metabox
        add_action('add_meta_boxes', [$this, 'addMetabox']);
        add_filter('woocommerce_layout_template_after_instantiation', [$this, 'addProductDataTab'], 10, 3);
        add_filter('woocommerce_rest_prepare_product_object', [$this, 'filterRestProduct'], 10, 3);
        add_filter('enrich_woocommerce_product', [$this, 'filterRestProduct'], 10, 3);
        add_filter('woocommerce_product_options_global_unique_id', [$this, 'addInformationOnInventory']);
    }

    public function addInformationOnInventory()
    {
        ?>
        <div class="inline notice woocommerce-message show_if_variable">
            <img class="info-icon"
                 src="<?php echo esc_url(plugins_url('assets/images', WC_PLUGIN_FILE) . '/icons/info.svg'); ?>"/>
            <p>
                <?php echo TextDomain::__("L'état du stock ou la quantité seront reliés entre toutes les traductions."); ?>
            </p>
        </div>
        <?php
        woocommerce_wp_select(
            array(
                'id' => 'traduire_sans_migraine_inventory_linked',
                'label' => TextDomain::__("Partager l'inventaire avec les traductions"),
                'options' => array(
                    '1' => TextDomain::__("Oui"),
                    '0' => TextDomain::__("Non"),
                ),
                'value' => get_post_meta(get_the_ID(), 'traduire_sans_migraine_inventory_linked', true) == 1 ? '1' : '0',
            )
        );
    }

    public function addMetabox()
    {
        add_meta_box(
            'traduire_sans_migraine_metabox',
            __('Traduire Sans Migraine', 'traduire_sans_migraine'),
            [$this, 'displayMetabox'],
            'product',
            'side',
            'high'
        );
    }


    public function addProductDataTab($layout_template_id, $layout_template_area, $layout_template)
    {
        $inventory = $layout_template->get_block('inventory');
        $section = $inventory->add_section(array(
            'id' => 'traduire-sans-migraine-section',
            'order' => 10,
            'attributes' => array(
                'title' => TextDomain::__("Traduire Sans Migraine"),
            ),
        ));
        // add select woocommerce
        $section->add_block(array(
            'id' => 'traduire-sans-migraine-inventory-linked',
            'blockName' => 'woocommerce/product-toggle-field',
            'order' => 10,
            'attributes' => array(
                'property' => 'traduire-sans-migraine-inventory-linked',
                'label' => TextDomain::__("Partager l'inventaire avec les traductions"),
                'checkedHelp' => TextDomain::__("L'état du stock ou la quantité seront reliés entre toutes les traductions."),
                'uncheckedHelp' => TextDomain::__("L'état du stock ou la quantité seront reliés entre toutes les traductions."),
            ),
        ));
        $group = $layout_template->add_group(array(
            'id' => "traduire_sans_migraine",
            'order' => 100,
            'attributes' => array(
                'title' => TextDomain::__("Traduire Sans Migraine"),
            ),
        ));
        $firstSection = $group->add_section(
            array(
                'id' => 'traduire-sans-migraine-language-section',
                'order' => 10,
                'attributes' => array(),
            )
        );
        $firstSection->add_block(
            array(
                'id' => 'traduire-sans-migraine-test',
                'blockName' => 'traduire-sans-migraine/woocommerce-languages',
                'order' => 40,
                'attributes' => array(
                    "ajaxUrl" => admin_url('admin-ajax.php'),
                    "nonce" => wp_create_nonce("traduire-sans-migraine"),
                    "currentLocale" => get_locale(),
                    "polylangUrl" => defined("POLYLANG_FILE") ? plugin_dir_url(POLYLANG_FILE) : "",
                ),
            )
        );
    }

    public function filterRestProduct($response, $object, $request)
    {
        global $tsm;

        $productId = $object->get_id();
        $status = get_post_status($productId);
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $translations = [];
        foreach ($languages as $slug => $language) {
            $translations[$slug] = null;
        }
        if (!$status || $status == "trash" || $status == "auto-draft") {
            $languageId = null;
            foreach ($languages as $slug => $language) {
                if ($language["default"]) {
                    $languageId = $language["id"];
                    break;
                }
            }
        } else {
            $translation = TranslationPost::findTranslationFor($productId);
            foreach ($translation->getTranslations() as $slug => $id) {
                if (empty($id)) {
                    $translations[$slug] = null;
                    continue;
                }
                $status = get_post_status($id);
                if (!$status || $status == "trash" || $status == "auto-draft") {
                    $translations[$slug] = null;
                    continue;
                }
                $translations[$slug] = [
                    "label" => get_post_field("post_name", $id),
                    "id" => $id,
                    "value" => $id
                ];
            }
            $language = LanguagePost::getLanguage($productId);
            $languageId = isset($language["id"]) ? $language["id"] : null;
        }

        $response->data['translations'] = $translations;
        $response->data['selectedLanguage'] = $languageId;
        $response->data['traduire-sans-migraine-inventory-linked'] = get_post_meta($productId, "traduire_sans_migraine_inventory_linked", true) == 1;
        return $response;
    }

    public function displayMetabox($post)
    {
        $this->displayFieldLanguage($post);
        $this->displayFieldTranslations($post);
    }

    private function displayFieldLanguage($post)
    {
        ?>
        <div style="display: flex; flex-direction: column; gap: 0.5rem; justify-content: center; align-items: center">
            <label for="traduire_sans_migraine_language"><?php echo TextDomain::__("Sélectionner la langue associé à ce produit"); ?></label>
            <?php
            do_action("tsm-display-language-select", $post, true);
            ?>
        </div>
        <?php
    }

    private function displayFieldTranslations($postArg)
    {
        do_action("tsm-display-translations-table", $postArg, true);
    }

    public function enqueueScripts()
    {
        global $pagenow;
        if (!is_admin() || ($pagenow !== "post.php" && $pagenow !== "post-new.php")) {
            return;
        }
        do_action("tsm-enqueue-admin-scripts");
    }
}

$Display = new Display();
$Display->init();
