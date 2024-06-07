<?php

namespace TraduireSansMigraine\Wordpress\Hooks;


use TraduireSansMigraine\Front\Components\Step;
use TraduireSansMigraine\Languages\LanguageManager;
use TraduireSansMigraine\Wordpress\LinkManager;
use TraduireSansMigraine\Wordpress\Queue;
use TraduireSansMigraine\Wordpress\TextDomain;
use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\TranslateHelper;

if (!defined("ABSPATH")) {
    exit;
}

class RestAPI
{
    private $settings;

    public function __construct() {
        $this->settings = new Settings();
    }

    public function init() {
        add_action( 'rest_api_init', [$this, "registerEndpoints"]);
    }

    public function ping($data) {
        if (!isset($data["domainKey"])) {
            return new \WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
        }
        if ($data["domainKey"] !== $this->settings->getToken()) {
            return new \WP_REST_Response(["success" => false, "error" => "Domain key not valid"], 400);
        }
        return new \WP_REST_Response(["success" => true, "data" => "pong"], 200);
    }

    public function setTranslations($data) {
        set_time_limit(0); // Can be a long process cause of the lock system
        if (!isset($data["id"]) || !isset($data["dataToTranslate"]) || !isset($data["codeTo"]) || !isset($data["domainKey"])) {
            return new \WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
        }
        if ($data["domainKey"] !== $this->settings->getToken()) {
            return new \WP_REST_Response(["success" => false, "error" => "Domain key not valid"], 400);
        }

        $TranslateHelper = new TranslateHelper($data["id"], $data["dataToTranslate"], $data["codeTo"]);
        $TranslateHelper->handleTranslationResult();
        if ($TranslateHelper->isSuccess()) {
            $response = new \WP_REST_Response($data, 200);
        } else {
            $response = new \WP_REST_Response(["success" => false, "error" => $TranslateHelper->getError()], 500);
        }
        return $response;
    }

    public function registerEndpoints() {
        register_rest_route( 'seo-sans-migraine', '/translations/(?P<id>\d+)', [
            'methods' => 'POST',
            'callback' => [$this, "setTranslations"],
            'permission_callback' => function () {
                return true;
            },
            'args' => [
                'id' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
                'dataToTranslate' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
                'codeTo' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
                'domainKey' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
            ],
        ]);

        register_rest_route( 'seo-sans-migraine', '/ping', [
            'methods' => 'GET',
            'callback' => [$this, "ping"],
            'permission_callback' => function () {
                return true;
            },
            'args' => [
                'domainKey' => [
                    'validate_callback' => function($param, $request, $key) {
                        return true;
                    }
                ],
            ],
        ]);
    }

    public static function getInstance() {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }
}