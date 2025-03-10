<?php

namespace TraduireSansMigraine\Wordpress;

use Plugin_Upgrader;

if (!defined("ABSPATH")) {
    exit;
}

class Requirements
{
    private static $instance = null;

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function handleRequirements()
    {
        if (!$this->checkPhpVersion()) {
            add_action("admin_notices", [$this, "noticePhp"]);
            return false;
        }
        if (!$this->canUseCURL()) {
            add_action("admin_notices", [$this, "noticeServerConfiguration"]);
            return false;
        }
        if ($this->haveWPML()) {
            add_action("admin_notices", [$this, "noticeWrongPluginWPML"]);
            return false;
        }
        if ($this->haveGTranslate()) {
            add_action("admin_notices", [$this, "noticeWrongPluginGTranslate"]);
            return false;
        }
        if ($this->haveWeglot()) {
            add_action("admin_notices", [$this, "noticeWrongPluginWeglot"]);
            return false;
        }
        if ($this->haveMultilingual()) {
            add_action("admin_notices", [$this, "noticeWrongPluginMultilingual"]);
            return false;
        }
        if (!$this->havePolylang()) {
            if ($this->activatePolylangIfAvailable()) {
                add_action('admin_notices', function () {
                    render_seoSansMigraine_alert(TextDomain::__("Traduire Sans Migraine"), TextDomain::__("Traduire Sans Migraine needs polylang to works. We found it on your website and enabled it automatically. If you really want to disable Polylang first disable Traduire Sans Migraine."), "success");
                });
                return true;
            }
            if (!wp_doing_ajax()) {
                add_action('admin_notices', function () {
                    ob_start();
                    ?>
                    <div style="display: flex; gap: 1rem; align-items: center;">
                        <?php echo TextDomain::__("Traduire Sans Migraine needs polylang to works. Don't worry you can install it simply by clicking on the button below."); ?>
                        <button class="button button-secondary" style="display: flex;"
                                id="install-polylang"><?php echo TextDomain::__("Install Polylang"); ?></button>
                    </div>
                    <script>
                        (() => {
                            const buttonInstall = document.querySelector("#install-polylang");
                            if (!buttonInstall) {
                                return;
                            }
                            buttonInstall.addEventListener("click", function () {
                                const data = {
                                    action: "install_polylang",
                                    wpnonce: "<?php echo wp_create_nonce('install_polylang'); ?>"
                                };
                                buttonInstall.disabled = true;
                                const Spinner = document.createElement("span");
                                Spinner.classList.add("spinner");
                                Spinner.classList.add("is-active");
                                buttonInstall.append(Spinner);
                                jQuery.post(ajaxurl, data, function (response) {
                                    if (window.location.indexOf("?") === -1) {
                                        window.location += "?tsm=polylang_installed";
                                    } else {
                                        window.location = `${window.location}&tsm=polylang_installed`;
                                    }
                                });
                            });
                        })();
                    </script>
                    <?php
                    render_seoSansMigraine_alert(TextDomain::__("Traduire Sans Migraine"), ob_get_clean(), "error");
                });
            }
            add_action("wp_ajax_install_polylang", [$this, "installPolylang"]);
            return false;
        }
        return true;
    }

    public function checkPhpVersion()
    {
        $requiredMinimumPhpVersion = TSM__PHP_REQUIREMENT;
        $phpIsValid = version_compare(PHP_VERSION, $requiredMinimumPhpVersion, ">=");

        return $phpIsValid;
    }

    private function canUseCURL()
    {
        global $tsm;

        return (function_exists("curl_version") && ($tsm->getClient()->fetchAccount() || $tsm->getClient()->getRedirect()));
    }

    public function haveWPML()
    {
        return class_exists('Weglot\Weglot') || is_plugin_active('sitepress-multilingual-cms/sitepress.php');
    }

    public function haveGTranslate()
    {
        return defined('GTRANSLATE_VERSION') || is_plugin_active('gtranslate/gtranslate.php');
    }

    public function haveWeglot()
    {
        return defined('ICL_SITEPRESS_VERSION') || is_plugin_active('weglot/weglot.php');
    }

    public function haveMultilingual()
    {
        return class_exists('Inpsyde\MultilingualPress\Framework\Api\Plugin') || is_plugin_active('multilingualpress/multilingualpress.php');
    }

    public function havePolylang()
    {
        return function_exists("pll_the_languages") || defined('POLYLANG_VERSION');
    }

    public function activatePolylangIfAvailable()
    {
        if (!function_exists('get_plugins')) {
            require_once ABSPATH . 'wp-admin/includes/plugin.php';
        }
        $plugins = get_plugins();
        foreach ($plugins as $path => $plugin) {
            if (strpos($plugin["Name"], "Polylang") !== false) {
                activate_plugin($path);
                delete_transient('pll_activation_redirect');
                $dismiss = get_option("pll_dismissed_notices", []);
                if (!is_array($dismiss)) {
                    $dismiss = [];
                }
                if (!in_array('wizard', $dismiss)) {
                    $dismiss[] = 'wizard';
                }
                if (!in_array('pllwc', $dismiss)) {
                    $dismiss[] = 'pllwc';
                }
                if (!empty($dismiss)) {
                    update_option("pll_dismissed_notices", $dismiss);
                }
                return true;
            }
        }
        return false;
    }

    public function installPolylang()
    {
        require_once(ABSPATH . 'wp-admin/includes/plugin-install.php');
        require_once(ABSPATH . 'wp-admin/includes/class-wp-upgrader.php');
        $api = plugins_api('plugin_information', array('slug' => 'polylang'));
        $upgrader = new Plugin_Upgrader();
        $install = $upgrader->install($api->download_link);
        if (is_wp_error($install)) {
            wp_send_json_error("Invalid installation");
        }
        $this->activatePolylangIfAvailable();
        wp_send_json_success();
        wp_die();
    }

    public function noticePhp()
    {
        render_seoSansMigraine_alert("PHP version is too low", sprintf("%s required at least PHP %s", TSM__NAME, TSM__PHP_REQUIREMENT), "error");
    }

    public function noticeServerConfiguration()
    {
        render_seoSansMigraine_alert("Server configuration issue", sprintf("%s can't work because of a server configuration issue. Please contact your hosting provider to enable cURL", TSM__NAME), "error");
    }

    public function noticeWrongPluginWPML()
    {
        $this->wrongPlugin("WPML");
    }

    public function wrongPlugin($pluginName)
    {
        render_seoSansMigraine_alert("Problème de conflit", sprintf("%s ne peut pas fonctionner à cause d'un conflit causé par %s, vous devez le désactiver pour continuer.", TSM__NAME, $pluginName), "error");
    }

    public function noticeWrongPluginGTranslate()
    {
        $this->wrongPlugin("GTranslate");
    }

    public function noticeWrongPluginWeglot()
    {
        $this->wrongPlugin("Weglot");
    }

    public function noticeWrongPluginMultilingual()
    {
        $this->wrongPlugin("MultilingualPress");
    }
}