<?php

namespace TraduireSansMigraine\Front\Components;

class Button {
    public static function getHTML($content, $type, $id, $dataset = []) {
        ob_start();
        ?>
        <button
                data-default="<?php echo $content; ?>"
                id="<?php echo $id; ?>"
                class="traduire-sans-migraine-button traduire-sans-migraine-button--<?php echo $type; ?>"
            <?php
            foreach ($dataset as $key => $value) {
                echo "data-$key=\"$value\"";
            }
            ?>
        >
            <?php echo $content; ?>
        </button>
        <?php
        return ob_get_clean();
    }

    public static function render($content, $type, $id, $dataset = []) {
        echo self::getHTML($content, $type, $id, $dataset);
    }
}