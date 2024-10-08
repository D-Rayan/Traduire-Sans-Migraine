<?php

namespace TraduireSansMigraine\SeoSansMigraine;

if (!defined("ABSPATH")) {
    exit;
}

class Client
{

    private $client;

    private $account;
    private $redirect;

    private $cache = [];

    public function __construct()
    {
        $this->client = new BaseClient();
        $this->account = null;
        $this->loadCache();
    }

    public function fetchAccount() {
        $this->authenticate();
        $response = $this->client->get("/accounts");

        if (!$response["success"]) {
            if ($response["status"] < 400) {
                $this->redirect = $response["error"];
            }
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
        $this->authenticate();
        return $this->client->post("/debugs", $data);
    }

    public function sendReasonDeactivate($data) {
        $this->authenticate();
        return $this->client->post("/reasons-deactivate", $data);
    }

    public function startTranslation(array $dataToTranslate, string $codeFrom, string $codeTo, array $options = []): array {
        $this->authenticate();
        return $this->client->post("/translations", [
            "dataToTranslate" => $dataToTranslate,
            "codeFrom" => $codeFrom,
            "codeTo" => $codeTo,
            "restUrl" => get_rest_url(),
            "translateAssets" => $options["translateAssets"] ?? false,
            "version" => TSM__VERSION
        ]);
    }

    public function getTranslation($tokenId) {
        $this->authenticate();
        return $this->client->get("/translations/$tokenId");
    }

    public function fetchAllFinishedTranslations() {
        $this->authenticate();
        $response = $this->client->get("/translations");
        if (!$response["success"]) {
            return [];
        }
        return $response["data"]["translations"];
    }

    public function getLanguages() {
        if (isset($this->cache["getLanguages"])) {
            return $this->cache["getLanguages"];
        }
        $this->authenticate();
        $response = $this->client->get("/languages");
        if (!$response["success"]) {
            return [
                "languages" => [],
                "complete" => [],
                "glossaries" => []
            ];
        }
        $this->cache["getLanguages"] = $response["data"];
        $this->saveCache();
        return $response["data"];
    }

    public function getProducts() {
        $this->authenticate();
        $response = $this->client->get("/products");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["products"];
    }

    public function loadDictionary($language) {
        $this->authenticate();
        $response = $this->client->get("/glossaries?langTo=$language");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["glossaries"];
    }

    public function updateWordToDictionary($id, $entry, $translation, $langFrom) {
        $this->authenticate();
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
        $this->authenticate();
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
        $this->authenticate();
        $response = $this->client->delete("/glossaries/$id");
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function updateLanguageSettings($slug, $formality = null, $country = null) {
        $this->authenticate();
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
        $this->authenticate();
        $response = $this->client->post("/languages/enable", [
            "slug" => $language
        ]);
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function getAccount() {
        if (!$this->account) {
            $this->fetchAccount();
        }
        return $this->account;
    }

    public function getRedirect() {
        return $this->redirect;
    }

    private function authenticate() {
        global $tsm;
        $this->client->setAuthorization($tsm->getSettings()->getToken());

    }

    private function loadCache() {
        $this->cache = get_option("tsm-cache-client", []);
    }

    private function saveCache() {
        update_option("tsm-cache-client", $this->cache);
    }

    static public function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}