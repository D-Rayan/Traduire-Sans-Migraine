<?php

namespace TraduireSansMigraine\Front\Pages\LogIn;
use TraduireSansMigraine\Front\Components\Button;
use TraduireSansMigraine\Front\Components\Suggestions;
use TraduireSansMigraine\SeoSansMigraine\Client;
use TraduireSansMigraine\Wordpress\TextDomain;

class LogIn {
    public function __construct() {
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
        $this->loadAdminHooks();
    }

    public function isOtterLoggedIn() {
        $clientSeoSansMigraine = new Client();
        echo json_encode(["logged_in" => $clientSeoSansMigraine->checkCredential()]);
        wp_die();
    }

    public function sendHTML() {
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
                <div>".Button::getHTML(TextDomain::__("Log-in"), "primary", "log-in", ["href" => $urlToOpen])."</div>
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