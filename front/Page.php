<?php

namespace TraduireSansMigraine\Front;
class Page
{
    private static $mainData = [];

    protected static function injectApplication($application, $variableName = 'traduireSansMigraineVariables', $data = [])
    {
        $handle = strtolower($application) . '-app-traduire-sans-migraine';
        $pathAssets = plugin_dir_path(__FILE__) . 'build/' . $application . '/index.tsx.asset.php';
        $pathCss = plugin_dir_url(__FILE__) . 'build/' . $application . '/index.tsx.css';
        $pathJs = plugins_url('build/' . $application . '/index.tsx.js', __FILE__);
        if (!file_exists($pathAssets)) {
            return;
        }
        $assets = include $pathAssets;
        wp_enqueue_script($handle, $pathJs, $assets['dependencies'], $assets['version'], ['in_footer' => true]);
        wp_enqueue_style($handle, $pathCss, [], $assets['version']);
        self::localizeScript($handle, $variableName, $data);
    }

    private static function localizeScript($handle, $objectName, $data)
    {
        if (empty(self::$mainData)) {
            self::initMainData();
        }
        wp_localize_script($handle, $objectName, array_merge(self::$mainData, $data));
    }

    private static function initMainData()
    {
        global $tsm;

        self::$mainData = [
            'ajaxUrl' => admin_url('admin-ajax.php'),
            'nonce' => wp_create_nonce('traduire-sans-migraine'),
            'token' => $tsm->getSettings()->getToken(),
            'currentLocale' => get_locale(),
            'languages' => $tsm->getPolylangManager()->getLanguages(),
            "polylangUrl" => defined("POLYLANG_FILE") ? plugin_dir_url(POLYLANG_FILE) : "",
            'urlClient' => TSM__CLIENT_LOGIN_DOMAIN,
        ];
        if (isset($_GET["post"])) {
            self::$mainData['translations'] = $tsm->getPolylangManager()->getAllTranslationsPost($_GET["post"]);
            self::$mainData['firstVisitAfterTSMTranslatedIt'] = get_post_meta($_GET["post"], '_tsm_first_visit_after_translation', true);
            self::$mainData['hasTSMTranslatedIt'] = get_post_meta($_GET["post"], '_has_been_translated_by_tsm', true);
            self::$mainData['translatedFromSlug'] = get_post_meta($_GET["post"], '_translated_by_tsm_from', true);
            self::$mainData['summary'] = get_post_meta($_GET["post"], '_summary_translated_by_tsm', true);
            self::$mainData['postId'] = $_GET["post"];
            delete_post_meta($_GET["post"], '_tsm_first_visit_after_translation');
        }
    }
}