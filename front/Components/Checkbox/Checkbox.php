<?php

namespace TraduireSansMigraine\Front\Components;

class Checkbox {
    public static function getHTML($label, $id, $checked = false, $disabled = false) {
        ob_start();
        ?>
        <div class="traduire-sans-migraine-checkbox">
            <input <?php echo $disabled ? "disabled" : ""; ?> type="checkbox" class="substituted" aria-hidden="true" id="<?php echo $id; ?>" name="<?php echo $id; ?>" <?php echo $checked ? "checked" : ""; ?>>
            <label for="<?php echo $id; ?>"><?php echo $label; ?></label>
        </div>
        <?php
        return ob_get_clean();
    }

    public static function render($label, $id, $checked = false, $disabled = false) {
        echo self::getHTML($label, $id, $checked, $disabled);
    }
}