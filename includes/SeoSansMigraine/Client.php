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
    private $redirect;

    public function __construct()
    {
        $this->client = new BaseClient();
        $this->settings = new Settings();
        $this->account = null;
    }

    public function fetchAccount() {
        $this->client->setAuthorization($this->settings->getToken());
        $response = $this->client->get("/accounts");
        if (!$response["success"]) {
            return false;
        }

        if ($response["status"] >= 300) {
            $this->redirect = $response["data"];
            return false;
        }

        $this->account = $response["data"];

        return true;
    }

    public function checkCredential() {
        $this->fetchAccount();

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

    public function getLanguages() {
        $response = $this->client->get("/languages");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["languages"];
    }

    public function getProducts() {
        $response = $this->client->get("/products");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["products"];
    }

    public function getAccount() {
        return $this->account;
    }

    public function getRedirect() {
        return $this->redirect;
    }
}