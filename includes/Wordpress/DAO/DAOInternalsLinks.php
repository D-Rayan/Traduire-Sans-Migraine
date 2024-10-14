<?php

namespace TraduireSansMigraine\Wordpress\DAO;

class DAOInternalsLinks
{
    private static $TABLE_NAME = "tsm_internals_links";
    private static $instance = null;
    private $currentVersion;
    private $optionVersion = "tsm_internals_links_db_version";

    public function __construct()
    {
        $this->currentVersion = get_site_option($this->optionVersion) || '0.0.0';
    }

    public static function updateDatabaseIfNeeded()
    {
        $instance = self::getInstance();
        if ($instance->needUpdateDatabase()) {
            $instance->updateDatabase();
        }
    }

    public static function getInstance()
    {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public function needUpdateDatabase()
    {
        return version_compare($this->currentVersion, TSM__VERSION) < 0;
    }

    public function updateDatabase()
    {
        $this->installDatabaseVersion200();
        update_option($this->optionVersion, TSM__VERSION, true);
    }

    private function installDatabaseVersion200()
    {
        if (version_compare($this->currentVersion, '2.0.0') >= 0) {
            return;
        }
        global $wpdb;
        $stateEnum = "";
        foreach (self::$STATE as $state) {
            if ($stateEnum !== "") {
                $stateEnum .= ",";
            }
            $stateEnum .= "'$state'";
        }
        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "CREATE TABLE $tableName (
            `ID` INT NOT NULL AUTO_INCREMENT,
            `postId` BIGINT NOT NULL,
            `tokenId` VARCHAR(255) NOT NULL,
            `slugTo` VARCHAR(3) NOT NULL,
            `state` ENUM($stateEnum) NOT NULL,
            `origin` INT NOT NULL,
            `response` JSON NULL,
            `createdAt` VARCHAR(20) NOT NULL, 
            `updatedAt` VARCHAR(20) NOT NULL,
            `lock` VARCHAR(255) NULL,
            PRIMARY KEY (`ID`)
        ) $charsetCollate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }
}


