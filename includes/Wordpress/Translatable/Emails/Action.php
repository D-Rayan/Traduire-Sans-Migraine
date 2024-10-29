<?php

namespace TraduireSansMigraine\Wordpress\Translatable\Emails;

use TraduireSansMigraine\Settings;
use TraduireSansMigraine\Wordpress\AbstractClass\AbstractAction;
use TraduireSansMigraine\Wordpress\DAO\DAOActions;

class Action extends AbstractAction
{
    public function __construct($args, $isCopy = false)
    {
        $args["actionType"] = DAOActions::$ACTION_TYPE["EMAIL"];
        parent::__construct($args, $isCopy);
    }

    public function loadObject()
    {
        $emails = wc()->mailer()->get_emails();
        foreach ($emails as $email) {
            if ($email->id === $this->getObjectId()) {
                $this->object = $email;
                return;
            }
        }
    }

    protected function canExecute()
    {
        global $tsm;
        if (!$tsm->getSettings()->settingIsEnabled(Settings::$KEYS["enabledWoocommerce"])) {
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