<?php

namespace TraduireSansMigraine\SeoSansMigraine;

use TraduireSansMigraine\Cache;

if (!defined("ABSPATH")) {
    exit;
}

class BaseClient
{
    const BASE_URL = TSM__API_DOMAIN;
    static $Authorization = false;
    private $cache;

    public function __construct()
    {
        $this->cache = new Cache($this);
    }

    public function setAuthorization(string $bearer)
    {

        self::$Authorization = $this->getDomain() . ":" . $bearer;
    }

    public function getDomain()
    {
        $protocols = array('http://', 'https://', 'http://www.', 'https://www.', 'www.');
        $domain = explode("/", str_replace($protocols, '', site_url()))[0];
        return $domain;
    }

    public function post(string $url, array $body, array $headers = [], array $params = []): array
    {
        return $this->makeRequest("POST", $url, $headers, $body, $params);
    }

    private function makeRequest($method, $url, $headers = [], $body = [], $params = []): array
    {
        $fullUrl = self::BASE_URL . ($url[0] === "/" ? $url : "/$url");
        if (!empty($params)) {
            $fullUrl .= '?' . http_build_query($params);
        }

        $curl = curl_init($fullUrl);
        curl_setopt($curl, CURLOPT_RETURNTRANSFER, true);
        curl_setopt($curl, CURLOPT_CUSTOMREQUEST, $method);
        if (self::$Authorization) {
            $headers[] = "x-api-key: " . self::$Authorization;
        }
        $headers[] = "Content-Type: application/json";
        if (!empty($headers)) {
            curl_setopt($curl, CURLOPT_HTTPHEADER, $headers);

        }
        if (!empty($body)) {
            curl_setopt($curl, CURLOPT_POSTFIELDS, json_encode($body));
        }

        $response = curl_exec($curl);
        $error = curl_error($curl);
        $httpCode = curl_getinfo($curl, CURLINFO_HTTP_CODE);

        if ($error) {
            $result = [
                'success' => false,
                'error' => $error,
                'status' => $httpCode,
                'data' => null
            ];
        } else if ($httpCode >= 300) {
            $result = [
                'success' => false,
                'error' => json_decode($response, true),
                'status' => $httpCode,
                'data' => null
            ];
        } else {
            $result = [
                'success' => true,
                'error' => null,
                'status' => $httpCode,
                'data' => json_decode($response, true)
            ];
        }
        curl_close($curl);

        return $result;
    }

    public function put(string $url, array $body, array $headers = [], array $params = []): array
    {
        return $this->makeRequest("PUT", $url, $headers, $body, $params);
    }

    public function patch(string $url, array $body, array $headers = [], array $params = []): array
    {
        return $this->makeRequest("PATCH", $url, $headers, $body, $params);
    }

    public function get(string $url, array $params = [], array $headers = []): array
    {
        $args = [
            'url' => $url,
            'params' => $params,
            'headers' => $headers
        ];
        $cache = $this->cache->getCache(__FUNCTION__, $args);
        if (!empty($cache)) {
            return $cache;
        }
        $response = $this->makeRequest("GET", $url, $headers, [], $params);
        $this->cache->setCache(__FUNCTION__, $args, $response, Cache::$EXPIRATION["ONLY_MEMORY"]);
        return $response;
    }

    public function delete(string $url, array $params = [], array $headers = []): array
    {
        return $this->makeRequest("DELETE", $url, $headers, [], $params);
    }
}