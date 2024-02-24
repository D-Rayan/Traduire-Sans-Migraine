<?php

namespace TraduireSansMigraine\SeoSansMigraine;

use TraduireSansMigraine\Settings;

if (!defined("ABSPATH")) {
    exit;
}

class Client
{

    private $client;
    private $settings;

    private $account;

    public function __construct()
    {
        $this->client = new BaseClient();
        $this->settings = new Settings();
        $this->account = null;
    }

    public function getAccount() {
        $this->client->setAuthorization($this->settings->getToken());
        $response = $this->client->get("/accounts");

        if ($response["success"]) {
            $this->account = $response["data"];
        }

        return $this->account;
    }

    public function checkCredential() {
        $this->getAccount();

        return $this->account !== null;
    }

    public function startTranslation(array $dataToTranslate, string $codeFrom, string $codeTo): array {
        return $this->client->post("/translations", [
            "dataToTranslate" => $dataToTranslate,
            "codeFrom" => $codeFrom,
            "codeTo" => $codeTo,
            "restUrl" => get_rest_url(),
        ]);
    }

    public function getProducts() {
        return [
            ["name" => "SEO Sans Migraine", "description" => "Un plugin pour traduire vos articles sans effort", "image" => "https://traduire-sans-migraine.com/wp-content/uploads/2021/07/seo-sans-migraine.png"],
        ];
    }
}