<?php

namespace TraduireSansMigraine\Front\Components;

class Tooltip {

    public static function getHTML($innerHTML, $tooltipMessage, $options = []) {
        ob_start();
        ?>
        <span class="traduire-sans-migraine-tooltip">
            <div class="traduire-sans-migraine-tooltip-content <?php if (isset($options["padding"])) { echo "traduire-sans-migraine-tooltip-content-padding"; } ?>">
                <?php echo $tooltipMessage; ?>
            </div>
            <span class="traduire-sans-migraine-tooltip-hoverable"><?php echo $innerHTML; ?></span>
        </span>
        <?php
        return ob_get_clean();
    }

    public static function render($innerHTML, $tooltipMessage, $options = []) {
        echo self::getHTML($innerHTML, $tooltipMessage, $options);
    }
}