<?php

namespace TraduireSansMigraine\Wordpress\Translatable\AbstractClass;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable;

if (!defined("ABSPATH")) {
    exit;
}

abstract class AbstractPrepareTranslation
{

    protected $dataToTranslate;
    protected $object;
    protected $codeTo;

    /**
     * @var AbstractAction $action
     */
    protected $action;

    public function __construct(&$action)
    {
        $this->object = $action->getObject();
        $this->codeTo = $action->getSlugTo();
        $this->action = $action;
        $this->dataToTranslate = [];
    }

    public static function getInstance($action)
    {
        $actionType = $action->getActionType();
        if ($actionType === DAOActions::$ACTION_TYPE["EMAIL"]) {
            return new Translatable\Emails\PrepareTranslation($action);
        } else if ($actionType === DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]) {
            return new Translatable\Posts\ElementorModel\PrepareTranslation($action);
        } else if ($actionType === DAOActions::$ACTION_TYPE["TERMS"]) {
            return new Translatable\Terms\PrepareTranslation($action);
        } else if ($actionType === DAOActions::$ACTION_TYPE["ATTRIBUTES"]) {
            return new Translatable\Attributes\PrepareTranslation($action);
        } else if ($actionType === DAOActions::$ACTION_TYPE["PRODUCT"]) {
            return new Translatable\Posts\Products\PrepareTranslation($action);
        }

        return new Translatable\Posts\PrepareTranslation($action);
    }

    public function startTranslateExecute()
    {
        global $tsm;

        $result = $tsm->getClient()->checkCredential();
        if (!$result) {
            return seoSansMigraine_returnLoginError();
        }
        $codeFrom = $this->getSlugOrigin();
        $this->loadDataToTranslate();

        $result = $tsm->getClient()->startTranslation($this->dataToTranslate, $codeFrom, $this->codeTo, [
            "translateAssets" => $tsm->getSettings()->settingIsEnabled(Settings::$KEYS["translateAssets"])
        ]);
        if ($result["success"]) {
            if (isset($result["data"]["backgroundProcess"])) {
                update_option("_seo_sans_migraine_backgroundProcess", $result["data"]["backgroundProcess"], false);
            }
        } else if (!empty($result) && isset($result["error"]) && $result["error"]["code"] === "U004403-001") {
            $result["data"] = [
                "reachedMaxQuota" => true,
                "estimatedQuota" => intval(explode(": ", $result["error"]["message"])[1])
            ];
        } else if (!empty($result) && isset($result["error"]) && ($result["error"]["code"] === "U004403-002" || $result["error"]["code"] === "U004403-003")) {
            $result["data"] = [
                "reachedMaxLanguages" => true,
            ];
        } else if (!empty($result) && isset($result["error"])) {
            $result["data"] = [
                "error" => $result["error"]
            ];
        } else {
            $result["data"] = ["error" => "unknown", $result];
        }
        return [
            "success" => $result["success"],
            "data" => $result["data"]
        ];
    }

    abstract protected function getSlugOrigin();

    protected function loadDataToTranslate()
    {
        $this->prepareDataToTranslate();
        $this->addChildrenDataToTranslate();
    }

    abstract protected function prepareDataToTranslate();

    private function addChildrenDataToTranslate()
    {
        $children = $this->action->getChildren();
        if (!is_array($children)) {
            return;
        }
        foreach ($children as $child) {
            $this->dataToTranslate = array_merge($this->dataToTranslate, $child->getDataToTranslate());
        }
    }

    public function getDataToTranslate()
    {
        if (empty($this->dataToTranslate)) {
            $this->loadDataToTranslate();
        }
        return $this->dataToTranslate;
    }
}