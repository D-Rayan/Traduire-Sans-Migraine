<?php

namespace TraduireSansMigraine\SeoSansMigraine;

use TraduireSansMigraine\Front\Components\Alert;
use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TextDomain;

if (!defined("ABSPATH")) {
    exit;
}

class Client
{

    private $client;
    private $settings;

    public function __construct()
    {
        $this->client = new BaseClient();
        $this->settings = new Settings();
    }

    public function checkCredential(string $token) {
        $this->client->setAuthorization($token);
        $response = $this->client->get("/accounts");
        if (($response["success"] === false || !isset($response["data"]["slug"]) || !isset($response["data"]["quota"])) &&
            !((defined('DOING_CRON') && DOING_CRON) || (defined('DOING_AJAX') && DOING_AJAX))) {
            add_action( 'admin_notices', function () {
                Alert::render(TextDomain::__("Activate your plugin"), TextDomain::__("You need to activate the licence of your plugin"), "error");
            });
        }
        return $response["success"];
    }

    public function startTranslation(array $dataToTranslate, string $codeFrom, string $codeTo): array {
        return $this->client->post("/translations", [
            "dataToTranslate" => $dataToTranslate,
            "codeFrom" => $codeFrom,
            "codeTo" => $codeTo,
            "restUrl" => get_rest_url(),
        ]);
    }
}