<?php

namespace TraduireSansMigraine\Front\Pages\Plugins\ReasonsDeactivate;

use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Checkbox;
use TraduireSansMigraine\Front\Components\Modal;
use TraduireSansMigraine\Wordpress\TextDomain;

class ReasonsDeactivate
{

    private $path;

    public function __construct()
    {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts()
    {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "ReasonsDeactivate.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "ReasonsDeactivate.min.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin()
    {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"]);
        add_action("admin_enqueue_scripts", [$this, "enqueueStyles"]);
    }

    public function loadAssetsClient()
    {
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

    public function loadHooksAdmin()
    {
        add_action("wp_ajax_traduire-sans-migraine_plugins_reasons_deactivate_render", [$this, "render"]);
    }

    public function render()
    {
        if (!isset($_GET["wp_nonce"]) || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_plugins_reasons_deactivate")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $reasons = [
            [
                "value" => "no-need-anymore",
                "label" => "I don't need it anymore",
            ],
            [
                "value" => "use-concurrent",
                "label" => "I will use a concurrent",
            ],
            [
                "value" => "got-bugs",
                "label" => "I had bugs",
            ],
            [
                "value" => "break-temporary",
                "label" => "Just a quick deactivation",
            ],
            [
                "value" => "other",
                "label" => "Others",
            ]
        ];
        shuffle($reasons);
        ob_start();
        ?>
        <div class="reason-deactivate-row">
            <div class="form-reason-deactivate">
                <?php
                foreach ($reasons as $index => $reason) {
                    ?>
                    <div>
                        <input type="radio" id="reason-deactivate-<?php echo $index; ?>" name="reason-deactivate" value="<?php echo $reason["value"]; ?>"/>
                        <label for="reason-deactivate-<?php echo $index; ?>"><?php echo TextDomain::__($reason["label"]) ?></label>
                    </div>
                    <?php
                }
                Button::render(TextDomain::__("Send & Deactivate"), "danger", "send-reason-deactivate", [
                    "wp_nonce" => wp_create_nonce("traduire-sans-migraine_plugins_reasons_deactivate_send")
                ]);
                Checkbox::render(TextDomain::__("Also delete all configuration related to %s", TSM__NAME), "delete-configuration");
                ?>
            </div>
            <div class="reason-deactivate-picture">
                <img src="<?php echo TSM__ASSETS_PATH; ?>loutre_triste.png" alt="sad otter" />
            </div>
        </div>
        <?php
        $htmlContent = ob_get_clean();
        Modal::render("Why are you deactivating it?", $htmlContent, [
            Button::getHTML(TextDomain::__("Skip & Disable"), "ghost", "skip-disable")
        ], ["size" => "little"]);
        wp_die();
    }

    public function loadHooksClient()
    {
        // nothing to load
    }

    public function loadHooks()
    {
        if (is_admin()) {
            $this->loadHooksAdmin();
        } else {
            $this->loadHooksClient();
        }
    }

    public function init()
    {
        $this->loadAssets();
        $this->loadHooks();
    }
}

$reasonsDeactivate = new ReasonsDeactivate();
$reasonsDeactivate->init();