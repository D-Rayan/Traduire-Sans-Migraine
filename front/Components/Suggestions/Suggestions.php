<?php

namespace TraduireSansMigraine\Front\Components;

class Suggestions {

    public static function getHTML($title, $message, $footer, $options = []) {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-suggestion <?php if (isset($options["classname"])) { echo $options["classname"]; } ?>">
            <div class="traduire-sans-migraine-suggestion-title"><?php echo $title; ?></div>
            <div class="traduire-sans-migraine-suggestion-content"><?php echo $message; ?></div>
            <div class="traduire-sans-migraine-suggestion-footer"><?php echo $footer; ?></div>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render($title, $message, $footer, $options = []) {
        echo self::getHTML($title, $message, $footer, $options);
    }
}