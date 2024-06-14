<?php

namespace TraduireSansMigraine\Front\Components;

class Modal {

    public static function render($title, $message, $buttons = [], $options = []) {
        ?>
        <div class="traduire-sans-migraine-modal <?php if (isset($options["size"])) { echo 'traduire-sans-migraine-modal-size-' . $options["size"]; } ?>">
            <div class="traduire-sans-migraine-overlay"></div>
            <div class="traduire-sans-migraine-modal__content">
                <div class="traduire-sans-migraine-modal__content-header">
                    <div class="traduire-sans-migraine-modal__header-left">
                        <span class="traduire-sans-migraine-modal__content-header-title"><?php echo $title; ?></span>
                        <?php if (count($buttons) > 0) { ?>
                            <div class="traduire-sans-migraine-modal__content-header-buttons">
                                <?php
                                foreach ($buttons as $button) {
                                    echo $button;
                                }
                                ?>
                            </div>
                        <?php } ?>
                    </div>
                    <span class="traduire-sans-migraine-modal__content-header-close">X</span>
                </div>
                <div class="traduire-sans-migraine-modal__content-body">
                    <div class="traduire-sans-migraine-modal__content-body-text">
                        <?php echo $message; ?>
                    </div>
                </div>
            </div>
        </div>
        <?php
    }
}