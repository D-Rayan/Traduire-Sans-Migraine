<?php

namespace TraduireSansMigraine\Front;

class SettingsPage {
    public static function render() {
        global $tsm;
        ?>
        <div id="settings-app-traduire-sans-migraine">
            Chargement <span class="spinner is-active"></span>
        </div>
        <?php
        $asset_file = plugin_dir_path( __FILE__ ) . 'build/Settings/index.tsx.asset.php';
        if ( ! file_exists( $asset_file ) ) {
            return;
        }
        $asset = include $asset_file;

        wp_enqueue_script( 'settings-page-app', plugins_url( 'build/Settings/index.tsx.js', __FILE__ ), $asset['dependencies'], $asset['version'], ['in_footer' => true]);
        $assetsCss = plugin_dir_url( __FILE__ ) . 'build/Settings/index.tsx.css';
        wp_enqueue_style( 'settings-page-app', $assetsCss, [], $asset['version']);
        wp_localize_script( 'settings-page-app', 'traduireSansMigraineVariables', [
            'ajaxUrl' => admin_url( 'admin-ajax.php' ),
            'nonce' => wp_create_nonce( 'traduire-sans-migraine' ),
            'token' => $tsm->getSettings()->getToken(),
            'currentLocale' => get_locale(),
            'languages' => $tsm->getPolylangManager()->getLanguages(),
            "polylangUrl" => defined("POLYLANG_FILE") ? plugin_dir_url(POLYLANG_FILE) : "",
            'urlClient' => TSM__CLIENT_LOGIN_DOMAIN,
        ]);
    }
}