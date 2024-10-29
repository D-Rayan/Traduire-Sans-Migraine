<?php

namespace TraduireSansMigraine\Woocommerce\Email\Admin;

use TraduireSansMigraine\Wordpress\TextDomain;

class Display
{
    public function __construct()
    {

    }

    public function init()
    {
        if (!is_admin()) {
            return;
        }
        add_action('woocommerce_loaded', [$this, 'addFilters']);
    }

    public function addFilters()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            add_filter('woocommerce_settings_api_form_fields_' . $email->id, function ($fields) use ($email) {
                return $this->registerFieldsToMailForm($fields, $email);
            });
        }

        add_filter('woocommerce_generate_tsm-language-selector_html', [$this, 'displaySelector'], 10, 4);
        add_filter('woocommerce_generate_tsm-language-header_html', [$this, 'displayEmailHeadingField'], 10, 4);
        add_filter('woocommerce_generate_tsm-language-subject_html', [$this, 'displayEmailSubjectField'], 10, 4);
        add_filter('woocommerce_generate_tsm-language-additional_content_html', [$this, 'displayEmailAdditionalContentField'], 10, 4);
    }

    public function registerFieldsToMailForm($fields, $email)
    {
        global $tsm;
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $preSelectedLanguage = isset($_GET["defaultLanguage"]) ? $_GET["defaultLanguage"] : null;
        foreach ($languages as $slug => $language) {
            if ($language["default"]) {
                $fields["tsm-language-selector"] = [
                    'order' => 3,
                    'id' => "tsm-language-selector",
                    'name' => "tsm-language-selector",
                    'title' => TextDomain::__('Language Selector 🦦'),
                    'type' => 'tsm-language-selector',
                    'description' => TextDomain::__('Select the language you want to update! Only those fields will be displayed.'),
                    'default' => $preSelectedLanguage ?? $slug,
                    'desc_tip' => true,
                ];
                continue;
            }
            $fields = $this->registerFieldLanguage($fields, $language, $email);
        }

        foreach ($fields as $key => $field) {
            if (isset($field['order'])) {
                continue;
            }
            if (strstr($key, "enable")) {
                $fields[$key]['order'] = 1;
            } else if (strstr($key, "recipient")) {
                $fields[$key]['order'] = 2;
            } else if (strstr($key, "heading")) {
                $fields[$key]['order'] = 4;
            } else if (strstr($key, "subject")) {
                $fields[$key]['order'] = 5;
            } else if (strstr($key, "additional_content")) {
                $fields[$key]['order'] = 6;
            } else {
                $fields[$key]['order'] = 7;
            }
        }

        uasort($fields, function ($a, $b) {
            return $a['order'] - $b['order'];
        });

        return $fields;
    }

    private function registerFieldLanguage($fields, $language, $email)
    {
        if (!isset($fields["heading"]) || !isset($fields["subject"]) || !isset($fields["additional_content"])) {
            return $fields;
        }
        $slug = $language["code"];

        $key = "tsm-language-" . $slug . "-header";
        $fields[$key] = [
            "title" => $fields["heading"]["title"],
            "description" => $fields["heading"]["description"],
            "desc_tip" => $fields["heading"]["desc_tip"],
            'id' => $email->get_field_key($key),
            'name' => $email->get_field_key($key),
            'type' => "tsm-language-header",
            'order' => 4,
            'default' => $email->settings[$key] ?? "",
            "language" => $slug,
        ];


        $key = "tsm-language-" . $slug . "-subject";
        $fields[$key] = [
            "title" => $fields["subject"]["title"],
            "description" => $fields["subject"]["description"],
            "desc_tip" => $fields["subject"]["desc_tip"],
            'id' => $email->get_field_key($key),
            'name' => $email->get_field_key($key),
            'type' => "tsm-language-subject",
            'order' => 5,
            'default' => $email->settings[$key] ?? "",
            "language" => $slug,
        ];


        $key = "tsm-language-" . $slug . "-additional_content";
        $fields[$key] = [
            "title" => $fields["additional_content"]["title"],
            "description" => $fields["additional_content"]["description"],
            "desc_tip" => $fields["additional_content"]["desc_tip"],
            'id' => $email->get_field_key($key),
            'name' => $email->get_field_key($key),
            'type' => "tsm-language-additional_content",
            'order' => 6,
            'default' => $email->settings[$key] ?? "",
            "language" => $slug,
        ];

        return $fields;
    }

    public function displaySelector($value, $key, $field, $instance)
    {
        global $tsm;
        $this->startDisplay();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        ?>
        <tr class="form-field" id="language_selector_line">
            <th scope="row" class="titledesc">
                <label for="<?php echo $field["id"]; ?>>"><?php echo $field["title"]; ?></label>
                <?php echo wc_help_tip($field["description"], true); ?>
            </th>
            <td class="forminp">
                <fieldset>
                    <select name="<?php echo $field["name"]; ?>" id="<?php echo $field["id"]; ?>">
                        <?php
                        $defaultSlug = "";
                        foreach ($languages as $slug => $language) {
                            $defaultSlug = $language["default"] ? $slug : $defaultSlug;
                            $selected = $slug === $field["default"] ? "selected" : "";
                            ?>
                            <option value="<?php echo $slug; ?>" <?php echo $selected; ?>><?php echo $language["name"]; ?></option>
                            <?php
                        }
                        ?>
                </fieldset>
            </td>
        </tr>
        <script type="text/javascript">
            document.addEventListener('DOMContentLoaded', function () {
                function addSlugToOriginal(input, value) {
                    if (!input) {
                        return;
                    }
                    input.closest("tr").setAttribute("data-slug", value);
                }

                const selectorLanguage = document.querySelector("#tsm-language-selector");
                if (!selectorLanguage) {
                    return;
                }
                addSlugToOriginal(document.querySelector("input[id*='_heading']"), '<?php echo $defaultSlug; ?>');
                addSlugToOriginal(document.querySelector("input[id*='_subject']"), '<?php echo $defaultSlug; ?>');
                addSlugToOriginal(document.querySelector("textarea[id*='additional_content']"), '<?php echo $defaultSlug; ?>');

                function displaySlug(slug) {
                    const allFieldsFromSlug = document.querySelectorAll(`tr[data-slug='${slug}']`);
                    const allOthersFieldsWithSlug = document.querySelectorAll(`tr:not([data-slug='${slug}'])[data-slug]`);
                    allOthersFieldsWithSlug.forEach((el) => {
                        el.classList.add("hidden");
                    });
                    allFieldsFromSlug.forEach((el) => {
                        el.classList.remove("hidden");
                    });
                }

                selectorLanguage.addEventListener("change", (e) => {
                    const newSlug = selectorLanguage.value;
                    displaySlug(newSlug);
                });
                displaySlug(selectorLanguage.value);
            });
        </script>
        <?php
        return $this->endDisplay();
    }

    private function startDisplay()
    {
        ob_start();
    }

    private function endDisplay()
    {
        return ob_get_clean();
    }

    public function displayEmailHeadingField($value, $key, $field, $instance)
    {
        global $tsm;
        $this->startDisplay();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $language = $languages[$field["language"]];
        ?>
        <tr class="form-field hidden" data-slug="<?php echo $field["language"]; ?>">
            <th scope="row">
                <label for="<?php echo $field["id"]; ?>"><?php echo $language["flag"]; ?>
                    <?php echo $field["title"]; ?>
                    <?php if (isset($field["description"]) && isset($field["desc_tip"])) {
                        echo wc_help_tip($field["description"], true);
                    } ?></label>
            </th>
            <td>
                <input name="<?php echo $field["name"]; ?>"
                       id="<?php echo $field["id"]; ?>" type="text"
                       value="<?php echo $field["default"]; ?>"/>
            </td>
        </tr>
        <?php
        return $this->endDisplay();
    }

    public function displayEmailSubjectField($value, $key, $field, $instance)
    {
        global $tsm;
        $this->startDisplay();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $language = $languages[$field["language"]];
        ?>
        <tr class="form-field hidden" data-slug="<?php echo $field["language"]; ?>">
            <th scope="row">
                <label for="<?php echo $field["id"]; ?>"><?php echo $language["flag"]; ?>
                    <?php echo $field["title"]; ?>
                    <?php if (isset($field["description"]) && isset($field["desc_tip"])) {
                        echo wc_help_tip($field["description"], true);
                    } ?>
                </label>
            </th>
            <td>
                <input name="<?php echo $field["name"]; ?>"
                       id="<?php echo $field["id"]; ?>" type="text"
                       value="<?php echo $field["default"]; ?>"/>
            </td>
        </tr>
        <?php
        return $this->endDisplay();
    }

    public function displayEmailAdditionalContentField($value, $key, $field, $instance)
    {
        global $tsm;
        $this->startDisplay();
        $languages = $tsm->getPolylangManager()->getLanguagesActives();
        $language = $languages[$field["language"]];
        ?>
        <tr class="form-field hidden" data-slug="<?php echo $field["language"]; ?>">
            <th scope="row">
                <label for="<?php echo $field["id"]; ?>"><?php echo $language["flag"]; ?>
                    <?php echo $field["title"]; ?>
                    <?php if (isset($field["description"]) && isset($field["desc_tip"])) {
                        echo wc_help_tip($field["description"], true);
                    } ?></label>
            </th>
            <td>
                <textarea name="<?php echo $field["name"]; ?>"
                          id="<?php echo $field["id"]; ?>"><?php echo $field["default"]; ?></textarea>
            </td>
        </tr>
        <?php
        return $this->endDisplay();
    }
}

$Display = new Display();
$Display->init();
