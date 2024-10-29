<?php

namespace TraduireSansMigraine\Wordpress\RestApi;

use TraduireSansMigraine\Wordpress\AbstractClass\AbstractApplyTranslation;
use WP_REST_Response;

if (!defined("ABSPATH")) {
    exit;
}

class Translations
{
    public function __construct()
    {
    }

    public function setTranslations($data)
    {
        global $tsm;
        set_time_limit(0);
        if (!isset($data["id"]) || !isset($data["dataToTranslate"]) || !isset($data["codeTo"]) || !isset($data["domainKey"])) {
            return new WP_REST_Response(["success" => false, "error" => "Missing parameters"], 400);
        }
        if ($data["domainKey"] !== $tsm->getSettings()->getToken()) {
            return new WP_REST_Response(["success" => false, "error" => "Domain key not valid"], 400);
        }


        $instanceTranslation = AbstractApplyTranslation::getInstance($data["id"], $data["dataToTranslate"]);
        if (!$instanceTranslation) {
            return new WP_REST_Response(["success" => false, "error" => "Action not found"], 404);
        }

        $translationIsSuccess = $instanceTranslation->applyTranslation();
        if ($translationIsSuccess === true) {
            $response = new WP_REST_Response(["success" => true], 200);
        } else {
            $response = new WP_REST_Response(["success" => false, "error" => $instanceTranslation->getResponse()], 500);
        }
        return $response;
    }

    public static function getInstance()
    {
        static $instance = null;
        if (null === $instance) {
            $instance = new static();
        }
        return $instance;
    }

    public function registerEndpoints()
    {
        register_rest_route('seo-sans-migraine', '/translations', [
            'methods' => 'POST',
            'callback' => [$this, "setTranslations"],
            'permission_callback' => function () {
                return true;
            },
            'args' => [
                'id' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return true;
                    }
                ],
                'dataToTranslate' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return true;
                    }
                ],
                'codeTo' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return true;
                    }
                ],
                'domainKey' => [
                    'validate_callback' => function ($param, $request, $key) {
                        return true;
                    }
                ],
            ],
        ]);
    }
}