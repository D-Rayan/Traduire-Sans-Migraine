<?php

namespace TraduireSansMigraine\Woocommerce\Terms\Admin;

use TraduireSansMigraine\Wordpress\TextDomain;

class Display
{
    public function __construct()
    {

    }

    public function init()
    {
        $taxonomies = apply_filters("tsm-wc-get-terms-allowed", []);
        foreach ($taxonomies as $taxonomy) {
            add_action($taxonomy . '_add_form_fields', [$this, 'addFieldsToCreateForm'], 100);
            add_action($taxonomy . '_edit_form_fields', [$this, 'addFieldsToEditForm'], 100, 2);
        }
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
    }

    public function enqueueScripts()
    {
        global $pagenow;
        if (!is_admin() || ($pagenow !== "edit-tags.php" && $pagenow !== "term.php")) {
            return;
        }
        do_action("tsm-enqueue-admin-scripts");
    }

    public function addFieldsToEditForm($term, $taxonomy)
    {
        ?>
        <tr class="form-field">
            <th scope="row"><label
                        for="traduire_sans_migraine_language"><?php echo TextDomain::__("Language"); ?></label></th>
            <td>
                <?php
                $this->displayFieldLanguage($term);
                ?>
            </td>
        </tr>
        <tr class="form-field">
            <th scope="row"><label
                        for="traduire_sans_migraine_translations"><?php echo TextDomain::__("Translations"); ?></label>
            </th>
            <td>
                <?php
                $this->displayFieldTranslations($term);
                ?>
            </td>
        </tr>
        <?php
    }

    private function displayFieldLanguage($term = null)
    {
        do_action("tsm-display-language-select", $term, false);
        ?>
        <p class="description"><?php echo TextDomain::__("Sélectionner la langue associé à cette catégorie"); ?></p>
        <?php
    }

    private function displayFieldTranslations($term = null)
    {
        do_action("tsm-display-translations-table", $term, false);
    }

    public function addFieldsToCreateForm()
    {
        ?>
        <div class="form-field">
            <label for="traduire_sans_migraine_language"><?php echo TextDomain::__("Language"); ?></label>
            <?php $this->displayFieldLanguage(); ?>
        </div>
        <div class="form-field">
            <label for="traduire_sans_migraine_translations"><?php echo TextDomain::__("Translations"); ?></label>
            <?php
            $this->displayFieldTranslations();
            ?>
        </div>
        <?php
    }
}

$Display = new Display();
$Display->init();
