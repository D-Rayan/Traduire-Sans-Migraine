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
    public static function getHTML($options = [])
    {
        if (!isset($options["percentage"])) {
            $options["percentage"] = 0;
        }
        if (!isset($options["status"])) {
            $options["status"] = self::$STEP_STATE["PROGRESS"];
        }
        if (!strstr($options["percentage"], "%")) {
            $options["percentage"] .= "%";
        }
        ob_start();
        ?>
        <div class="traduire-sans-migraine-step <?php echo $options["classname"]; ?>">
            <div class="indicator-percentage"></div>
            <div class="progress-bar">
                <div class="progress-bar-fill progress-bar-fill--<?php echo $options["status"]; ?>" style="width: <?php echo $options["percentage"]; ?>;"></div>
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