<?php

namespace TraduireSansMigraine\Front;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;

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
            $postId = $_GET["post"];
        } else if (isset($_GET["page"]) && $_GET["page"] === "wc-admin" && isset($_GET["path"])) {
            $tmp = explode("/", $_GET["path"]);
            foreach ($tmp as $value) {
                if (is_numeric($value)) {
                    $postId = $value;
                    break;
                }
            }
        }
        if (isset($postId)) {
            self::$mainData['objectId'] = $postId;
            self::$mainData['objectType'] = DAOActions::$ACTION_TYPE["POST_PAGE"];
        }
    }
}