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

    public function sendDebugData($data) {
        return $this->client->post("/debugs", $data);
    }

    public function sendReasonDeactivate($data) {
        return $this->client->post("/reasons-deactivate", $data);
    }

    public function startTranslation(array $dataToTranslate, string $codeFrom, string $codeTo, array $options = []): array {
        return $this->client->post("/translations", [
            "dataToTranslate" => $dataToTranslate,
            "codeFrom" => $codeFrom,
            "codeTo" => $codeTo,
            "restUrl" => get_rest_url(),
            "translateAssets" => $options["translateAssets"] ?? false,
        ]);
    }

    public function getTranslation($tokenId) {
        return $this->client->get("/translations/$tokenId");
    }

    public function fetchAllFinishedTranslations() {
        $response = $this->client->get("/translations");
        if (!$response["success"]) {
            return [];
        }
        return $response["data"]["translations"];
    }

    public function getLanguages() {
        $response = $this->client->get("/languages");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"];
    }

    public function getProducts() {
        $response = $this->client->get("/products");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["products"];
    }

    public function loadDictionary($language) {
        $response = $this->client->get("/glossaries?langTo=$language");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["glossaries"];
    }

    public function updateWordToDictionary($id, $entry, $translation, $langFrom) {
        $response = $this->client->put("/glossaries/$id", [
            "entry" => $entry,
            "result" => $translation,
            "langFrom" => $langFrom
        ]);
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function addWordToDictionary($entry, $translation, $langFrom, $langTo) {
        $response = $this->client->post("/glossaries", [
            "entry" => $entry,
            "result" => $translation,
            "langFrom" => $langFrom,
            "langTo" => $langTo
        ]);
        if (!$response["success"]) {
            return false;
        }

        return $response["data"]["glossaryId"];
    }

    public function deleteWordFromDictionary($id) {
        $response = $this->client->delete("/glossaries/$id");
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function updateLanguageSettings($slug, $formality = null, $country = null) {
        $body = [
            "slug" => $slug
        ];
        if ($formality !== null) {
            $body["formality"] = $formality;
        }
        if ($country !== null) {
            $body["country"] = $country;
        }
        $response = $this->client->put("/languages/settings", $body);
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function enableLanguage($language) {
        $response = $this->client->post("/languages/enable", [
            "slug" => $language
        ]);
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function getAccount() {
        return $this->account;
    }

    public function getRedirect() {
        return $this->redirect;
    }
}