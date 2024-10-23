<?php

namespace TraduireSansMigraine\Front;

class SettingsPage extends Page
{
    public static function render()
    {
        ?>
        <div id="settings-app-traduire-sans-migraine">
            Chargement <span class="spinner is-active"></span>
        </div>
        <?php
        self::injectApplication('Settings');
    }
}