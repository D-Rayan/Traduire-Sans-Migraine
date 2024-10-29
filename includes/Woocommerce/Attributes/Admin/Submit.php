<?php

namespace TraduireSansMigraine\Woocommerce\Attributes\Admin;

use TraduireSansMigraine\Wordpress\PolylangHelper\Translations\TranslationAttribute;

class Submit
{
    public function __construct()
    {

    }

    public function init()
    {
        add_action("woocommerce_attribute_added", [$this, "handleSubmitAttribute"], 10, 2);
        add_action("woocommerce_attribute_updated", [$this, "handleSubmitAttribute"], 10, 3);
        add_action("woocommerce_attribute_deleted", [$this, "handleDeleteAttribute"], 10, 3);
    }

    public function handleSubmitAttribute($id, $data, $oldSlug = false)
    {
        global $tsm;

        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $translations = TranslationAttribute::findTranslationFor($id);
        foreach ($languages as $slug => $language) {
            if (!isset($_POST['attribute_label_' . $slug])) {
                $translations->removeTranslation($slug);
                continue;
            }
            $translatedName = wc_clean(wp_unslash($_POST['attribute_label_' . $slug]));
            $translations->addTranslation($slug, $translatedName);
        }
        $translations->save();
    }

    public function handleDeleteAttribute($id, $name, $taxonomy)
    {
        $translations = TranslationAttribute::findTranslationFor($id);
        $translations->delete();
    }
}

$Submit = new Submit();
$Submit->init();
