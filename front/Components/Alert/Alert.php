<?php

namespace TraduireSansMigraine\Front\Components;

class Alert {
    public function __construct() {}

    public static function getHTML($title, $message, $type, $options = [
            "isDismissible" => true
    ]) {
        ob_start();
        if ($title && !empty($title)) {
            ?>
            <div class="notice traduire-sans-migraine-alert traduire-sans-migraine-alert-<?php echo $type; ?> <?php if (isset($options["classname"])) { echo $options["classname"]; } ?>">
                <div class="traduire-sans-migraine-alert__title">
                    <span class="traduire-sans-migraine-alert__title-text"><?php echo $title; ?></span>
                    <?php echo (isset($options["isDismissible"]) && $options["isDismissible"] ? '<span class="traduire-sans-migraine-alert__title-close">X</span>' : '') ; ?>
                </div>
                <div class="traduire-sans-migraine-alert__body">
                    <?php echo $message; ?>
                </div>
            </div>
            <?php
        } else {
            ?>
            <div class="notice traduire-sans-migraine-alert traduire-sans-migraine-alert-<?php echo $type; ?> <?php if (isset($options["classname"])) { echo $options["classname"]; } ?>">
                <div class="traduire-sans-migraine-alert__title">
                    <span></span>
                    <?php echo (isset($options["isDismissible"]) && $options["isDismissible"] ? '<span class="traduire-sans-migraine-alert__title-close">X</span>' : ''); ?>
                </div>
                <div>
                    <?php echo $message; ?>
                </div>
            </div>
            <?php
        }

        return ob_get_clean();
    }

    public static function render($title, $message, $type, $options = [
        "isDismissible" => true
    ]) {
        echo self::getHTML($title, $message, $type, $options);
    }
}