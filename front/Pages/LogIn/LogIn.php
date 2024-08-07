<?php

namespace TraduireSansMigraine\Front\Pages\LogIn;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Suggestions;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

class LogIn {

    private $path;

    public function __construct() {
        $this->path = plugin_dir_url(__FILE__);
    }

    public function enqueueScripts() {
        wp_enqueue_script(TSM__SLUG . "-" . get_class(), $this->path . "LogIn.js", [], TSM__VERSION, true);
    }

    public function enqueueStyles()
    {
        wp_enqueue_style(TSM__SLUG . "-" . get_class(), $this->path . "LogIn.min.css", [], TSM__VERSION);
    }

    public function loadAssetsAdmin() {
        add_action("admin_enqueue_scripts", [$this, "enqueueScripts"]);
        add_action("admin_enqueue_scripts", [$this, "enqueueStyles"]);
    }

    public function loadAssetsClient() {
        // nothing to load
    }
    public function loadAssets()
    {
        if (is_admin()) {
            $this->loadAssetsAdmin();
        } else {
            $this->loadAssetsClient();
        }
    }

    public function loadHooks() {

    }

    public function loadAdminHooks() {
        add_action("wp_ajax_traduire-sans-migraine_get_log_in_html", [$this, "sendHTML"]);
        add_action("wp_ajax_traduire-sans-migraine_is_otter_logged_in", [$this, "isOtterLoggedIn"]);
    }

    public function loadClientHooks() {
        // nothing here
    }
    public function init() {
        $this->loadAssets();
        $this->loadAdminHooks();
    }

    public function isOtterLoggedIn() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_is_otter_logged_in")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        $clientSeoSansMigraine = new Client();
        echo json_encode(["logged_in" => $clientSeoSansMigraine->checkCredential(), "wpNonce" => wp_create_nonce("traduire-sans-migraine_is_otter_logged_in")]);
        wp_die();
    }

    public function sendHTML() {
        if (!isset($_GET["wp_nonce"])  || !wp_verify_nonce($_GET["wp_nonce"], "traduire-sans-migraine_get_log_in_html")) {
            wp_send_json_error([
                "message" => TextDomain::__("The security code is expired. Reload your page and retry"),
                "title" => "",
                "logo" => "loutre_docteur_no_shadow.png"
            ], 400);
            wp_die();
        }
        echo self::getHTML();
        wp_die();
    }

    public static function getHTML() {
        $clientSeoSansMigraine = new Client();
        if ($clientSeoSansMigraine->checkCredential()) {
            return Suggestions::getHTML(TextDomain::__("Your otter 🦦"),
                TextDomain::__("You're already logged in. You can start translating your content."), "");
        }
        $urlToOpen = $clientSeoSansMigraine->getRedirect()["url"];
        return Suggestions::getHTML(TextDomain::__("Your otter 🦦"),
            TextDomain::__("You're not logged in. Please log-in to continue."),
            "<div class='suggestion-footer-settings' id='log-in'>
                <div>".Button::getHTML(TextDomain::__("Log-in"), "primary", "log-in", ["href" => $urlToOpen, "wp_nonce" => wp_create_nonce("traduire-sans-migraine_is_otter_logged_in")])."</div>
                <div class='right-footer'>
                    <img width='72' src='".TSM__ASSETS_PATH."loutre_ampoule.png' alt='loutre_ampoule' /></div></div>",
            ["classname" => "suggestion-settings"]
        );
    }

    static function render() {
        echo self::getHTML();
    }
}

$LogIn = new LogIn();
$LogIn->init();