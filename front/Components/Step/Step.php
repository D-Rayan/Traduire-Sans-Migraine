<?php

namespace TraduireSansMigraine\Front\Components;

use TraduireSansMigraine\Wordpress\TextDomain;

class Step
{
    static $STEP_STATE = [
        "PROGRESS" => "progress",
        "DONE" => "done",
        "ERROR" => "error"
    ];
    private $path;

    public function __construct()
    {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts()
    {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "Step.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "Step.css", [], TSM__VERSION);
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

    public static function getHTML($options = [])
    {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-step <?php echo $options["classname"]; ?>">
            <div class="indicator-percentage"></div>
            <div class="progress-bar">
                <div class="progress-bar-fill progress-bar-fill--progress" style="width: 0;"></div>
            </div>
            <div class="indicator-text"><?php if (isset($options["indicatorText"])) { echo $options["indicatorText"]; } ?></div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public static function render($options = [])
    {
        echo self::getHTML($options);
    }
}

$alert = new Step();
$alert->loadAssets();