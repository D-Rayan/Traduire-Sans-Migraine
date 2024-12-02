<?php

namespace TraduireSansMigraine\Wordpress\Filters;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\Translatable\AbstractClass\AbstractAction;


if (!defined("ABSPATH")) {
    exit;
}

class EnrichAction
{
    public function __construct()
    {
    }

    public function init()
    {
        add_filter("tsm-enrich-actions", [$this, "enrichActions"]);
    }

    public function enrichActions($originalAction)
    {
        if (empty($originalAction)) {
            return $originalAction;
        }
        $enrichedActions = [];
        $actions = (is_array($originalAction)) && (is_array($originalAction[0]) || is_object($originalAction[0])) ? $originalAction : [$originalAction];
        foreach ($actions as $key => $action) {
            $instance = is_object($action) ? $action : AbstractAction::getInstance($action);
            $enrichedActions[$key] = $instance->toArray(true);
            $enrichedActions[$key]["label"] = $this->getLabel($instance);
            $enrichedActions[$key]["link"] = $this->getLink($instance->getActionType(), $instance->getObjectId());
            $enrichedActions[$key]["linkToTranslated"] = $this->getLink($instance->getActionType(), $instance->getObjectIdTranslated(), $instance->getSlugTo());

        }
        return is_array($originalAction) ? $enrichedActions : $enrichedActions[0];
    }

    /**
     * @param AbstractAction $instance
     * @return mixed|string
     */
    private function getLabel($instance)
    {
        switch ($instance->getActionType()) {
            case DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]:
            case DAOActions::$ACTION_TYPE["POST_PAGE"]:
            case DAOActions::$ACTION_TYPE["PRODUCT"]:
                return $instance->getObject()->post_title;
            case DAOActions::$ACTION_TYPE["EMAIL"]:
                return $instance->getObject()->title;
            case DAOActions::$ACTION_TYPE["ATTRIBUTES"]:
            case DAOActions::$ACTION_TYPE["TERMS"]:
                return $instance->getObject()->name;
            default:
                return "Unknown";
        }
    }

    /**
     * @param AbstractAction $instance
     * @return string
     */
    private function getLink($actionType, $actionId, $language = "")
    {
        if (empty($actionId)) {
            return null;
        }
        switch ($actionType) {
            case DAOActions::$ACTION_TYPE["MODEL_ELEMENTOR"]:
            case DAOActions::$ACTION_TYPE["POST_PAGE"]:
            case DAOActions::$ACTION_TYPE["PRODUCT"]:
                return get_edit_post_link($actionId, "admin");
            case DAOActions::$ACTION_TYPE["EMAIL"]:
                return empty($language) ?
                    get_admin_url(null, "admin.php?page=wc-settings&tab=email&section=wc_email_" . $actionId) :
                    get_admin_url(null, "admin.php?page=wc-settings&tab=email&section=wc_email_" . $actionId . "&defaultLanguage=" . $language);
            case DAOActions::$ACTION_TYPE["ATTRIBUTES"]:
                return get_admin_url(null, "edit.php?post_type=product&page=product_attributes&edit=" . $actionId);
            case DAOActions::$ACTION_TYPE["TERMS"]:
                return get_edit_term_link($actionId);
            default:
                return "";
        }
    }
}

$EnrichAction = new EnrichAction();
$EnrichAction->init();