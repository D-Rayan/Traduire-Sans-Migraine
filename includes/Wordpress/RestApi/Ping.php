<?php

namespace TraduireSansMigraine\Wordpress\RestApi;

use WP_REST_Response;

if (!defined("ABSPATH")) {
    exit;
}

class Ping
{

    public function __construct()
    {
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function ping($data)
    {
        global $tsm;
        if (!isset($data["domainKey"])) {
            return new WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
        }
        if ($data["domainKey"] !== $tsm->getSettings()->getToken()) {
            return new WP_REST_Response(["success" => false, "error" => "Domain key not valid"], 400);
        }
        return new WP_REST_Response(["success" => true, "data" => "pong"], 200);
    }

    public function registerEndpoints()
    {
        register_rest_route('seo-sans-migraine', '/ping', [
            'methods' => 'GET',
            'callback' => [$this, "ping"],
            'permission_callback' => function () {
                return true;
            },
            'args' => [
                'domainKey' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return true;
                    }
                ],
            ],
        ]);
    }
}