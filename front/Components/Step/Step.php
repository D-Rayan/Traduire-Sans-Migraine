<?php

namespace TraduireSansMigraine\Front\Components;

class Step
{
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

    public static function getHTML($steps, $options = [])
    {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-step <?php echo $options["classname"]; ?>">
            <div>
                <ol>
                    <?php
                    foreach ($steps as $index => $step) {
                        ?>
                        <li>
                            <span class="icon">
                                <?php echo $index + 1; ?>
                            </span>
                            <span>
                                <?php echo $step; ?>
                            </span>
                        </li>
                        <?php
                    }
                    ?>
                </ol>
            </div>
        </div>
        <?php
        $content = ob_get_contents();
        ob_end_clean();
        return $content;
    }

    public static function render($steps, $options = [])
    {
        echo self::getHTML($steps, $options);
    }
}

$alert = new Step();
$alert->loadAssets();