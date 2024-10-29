<?php

namespace TraduireSansMigraine\Woocommerce\Email\Admin;

use TraduireSansMigraine\Wordpress\DAO\DAOActions;
use TraduireSansMigraine\Wordpress\TextDomain;

class Table
{
    public function __construct()
    {

    }

    public function init()
    {
        if (!is_admin() || !isset($_GET["page"]) || $_GET["page"] !== "wc-settings" || !isset($_GET["tab"]) || $_GET["tab"] !== "email") {
            return;
        }
        add_action('admin_init', [$this, 'addStyle']);
        add_filter('woocommerce_email_setting_columns', [$this, 'addColumn']);
    }

    public function addStyle()
    {
        do_action("tsm-enqueue-admin-scripts");
    }

    public function addColumn($columns)
    {
        $key = "traduire_sans_migraine_language";
        $columns[$key] = "";
        add_filter("woocommerce_email_setting_column_" . $key, [$this, "renderColumn"]);
        return $columns;
    }

    public function renderColumn($email)
    {
        echo '<td class="wc-email-settings-table-actions">
            <button id="display-traduire-sans-migraine-button" class="button alignright" data-objectId="' . $email->id . '" data-objectType="' . DAOActions::$ACTION_TYPE["EMAIL"] . '">' . TextDomain::__("Translate 💊") . '</button>
        </td>';
    }
}

$Table = new Table();
$Table->init();
