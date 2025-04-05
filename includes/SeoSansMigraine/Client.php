<?php

namespace TraduireSansMigraine\SeoSansMigraine;

use TraduireSansMigraine\Cache;

if (!defined("ABSPATH")) {
    exit;
}

class Client
{

    private $client;

    private $account;
    private $redirect;

    private $cache;

    public function __construct()
    {
        $this->client = new BaseClient();
        $this->account = null;
        $this->cache = new Cache($this);
    }


    static public function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function checkCredential()
    {
        $this->fetchAccount();

        return $this->account !== null;
    }

    public function fetchAccount()
    {
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

    private function authenticate()
    {
        global $tsm;
        $this->client->setAuthorization($tsm->getSettings()->getToken());

    }

    public function sendDebugData($data)
    {
        $this->authenticate();
        return $this->client->post("/debugs", $data);
    }

    public function sendReasonDeactivate($data)
    {
        $this->authenticate();
        return $this->client->post("/reasons-deactivate", $data);
    }

    public function startTranslation(array $dataToTranslate, string $codeFrom, string $codeTo, array $options = []): array
    {
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

    public function getEstimatedQuota(array $dataToTranslate, array $options = []): array
    {
        $this->authenticate();
        $args = ["dataToTranslate" => $dataToTranslate, "options" => $options];
        $cache = $this->cache->getCache(__FUNCTION__, $args);
        if (!empty($cache)) {
            return $cache;
        }
        $response = $this->client->post("/translations/estimate", [
            "dataToTranslate" => $dataToTranslate,
            "restUrl" => get_rest_url(),
            "translateAssets" => $options["translateAssets"] ?? false,
            "version" => TSM__VERSION
        ]);
        if ($response["success"]) {
            $this->cache->setCache(__FUNCTION__, $args, $response, Cache::$EXPIRATION["MAX"]);
        }
        return $response;
    }

    public function getTranslation($tokenId)
    {
        $this->authenticate();
        return $this->client->get("/translations/$tokenId");
    }

    public function fetchAllFinishedTranslations()
    {
        $this->authenticate();
        $response = $this->client->get("/translations");
        if (!$response["success"]) {
            return [];
        }
        return $response["data"]["translations"];
    }

    public function getLanguages()
    {
        $cache = $this->cache->getCache(__FUNCTION__);
        if (!empty($cache) && !empty($cache["languages"])) {
            return $cache;
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
        $this->cache->setCache(__FUNCTION__, [], $response["data"], Cache::$EXPIRATION["MAX"]);
        return $response["data"];
    }

    public function getProducts()
    {
        $cache = $this->cache->getCache(__FUNCTION__);
        if (!empty($cache)) {
            return $cache;
        }
        $this->authenticate();
        $response = $this->client->get("/products");
        if (!$response["success"]) {
            return [];
        }
        $this->cache->setCache(__FUNCTION__, [], $response["data"]["products"], Cache::$EXPIRATION["MAX"]);
        return $response["data"]["products"];
    }

    public function loadDictionary($language)
    {
        $this->authenticate();
        $response = $this->client->get("/glossaries?langTo=$language");
        if (!$response["success"]) {
            return [];
        }

        return $response["data"]["glossaries"];
    }

    public function updateWordToDictionary($id, $entry, $translation, $langFrom)
    {
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

    public function addWordToDictionary($entry, $translation, $langFrom, $langTo)
    {
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

    public function deleteWordFromDictionary($id)
    {
        $this->authenticate();
        $response = $this->client->delete("/glossaries/$id");
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function updateLanguageSettings($slug, $formality = null, $country = null)
    {
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

    public function enableLanguage($language)
    {
        $this->authenticate();
        $response = $this->client->post("/languages/enable", [
            "slug" => $language
        ]);
        if (!$response["success"]) {
            return false;
        }

        return true;
    }

    public function sendLog($message, $line, $file)
    {
        global $tsm;

        return $this->client->post("/logs", ["message" => $message, "version" => TSM__VERSION, "token" => $tsm->getSettings()->getToken(), "line" => $line, "file" => $file]);
    }

    public function getAccount()
    {
        if (!$this->account) {
            $this->fetchAccount();
        }
        return $this->account;
    }

    public function getRedirect()
    {
        return $this->redirect;
    }
}