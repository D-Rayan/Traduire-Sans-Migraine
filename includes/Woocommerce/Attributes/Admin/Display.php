<?php

namespace TraduireSansMigraine\Woocommerce\Attributes\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;
use TraduireSansMigraine\Wordpress\TextDomain;

class Display
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action('admin_enqueue_scripts', [$this, 'enqueueScripts']);
        add_action('woocommerce_after_edit_attribute_fields', [$this, 'addFieldsToEditForm']);
        add_action('woocommerce_after_add_attribute_fields', [$this, 'addFieldsToEditForm']);
    }

    public function addFieldsToEditForm()
    {
        global $tsm;
        $id = isset($_GET["edit"]) ? $_GET["edit"] : 0;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        ?>
        <tr class="form-field form-required">
            <th scope="row">

            </th>
            <td>
                Traduisez le nom de votre attribut dans les langues disponibles
            </td>
        </tr>
        <?php
        foreach ($languages as $slug => $language) {
            if ($language["default"]) {
                continue;
            }
            $this->displayFieldLanguage($id, $language);
        }
    }

    public function enqueueScripts()
    {
        global $pagenow;
        if (!is_admin() || $pagenow !== "edit.php" || !isset($_GET["page"]) || $_GET["page"] !== "product_attributes") {
            return;
        }
        do_action("tsm-enqueue-admin-scripts");
    }

    private function displayFieldLanguage($id, $language)
    {
        $translations = $id > 0 ? TranslationAttribute::findTranslationFor($id) : new TranslationAttribute();
        $translation = !empty($translations->getTranslation($language["code"])) ? $translations->getTranslation($language["code"]) : "";
        ?>
        <tr class="form-field form-required">
            <th scope="row">
                <label for="attribute_label_<?php echo $language["code"]; ?>"><?php echo $language["flag"]; ?><?php echo TextDomain::__('Name'); ?></label>
            </th>
            <td>
                <input name="attribute_label_<?php echo $language["code"]; ?>"
                       id="attribute_label_<?php echo $language["code"]; ?>" type="text"
                       value="<?php echo $translation; ?>"/>
            </td>
        </tr>
        <?php
    }
}

$Display = new Display();
$Display->init();
