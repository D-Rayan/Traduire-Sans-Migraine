<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Posts;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;

class Action extends AbstractAction
{

    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["POST_PAGE_PRODUCT"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        $this->object = get_post($this->getObjectId());
    }

    public function toArray($forClient = false)
    {
        $array = parent::toArray($forClient);

        return $array;
    }

    protected function canExecute()
    {
        global $tsm;
        $object = $this->getObject();
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"]) && !empty($object) && $object->post_type === "product") {
            $this->setAsError()->setResponse(["error" => "woocommerceDisabled"])->save();
            return false;
        }
        return parent::canExecute();
    }

    protected function getApplyTranslationInstance($data)
    {
        return new ApplyTranslation($this, $data);
    }
}