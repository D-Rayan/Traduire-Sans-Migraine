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

        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "CREATE TABLE $tableName (
            `ID` BIGINT NOT NULL AUTO_INCREMENT,
            `postId` BIGINT NOT NULL,
            `slugPost` VARCHAR(3) NOT NULL,
            `notTranslatedUrl` VARCHAR(255) NOT NULL,
            `notTranslatedPostId` BIGINT NOT NULL,
            `canBeFixed` TINYINT NOT NULL,
            `lock` VARCHAR(255) NULL,
            PRIMARY KEY (`ID`),
            UNIQUE KEY `postId_notTranslatedPostId` (`postId`, `notTranslatedPostId`)
        ) $charsetCollate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    public static function deleteTable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS $tableName");
    }

    public static function getById($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE ID = $id", ARRAY_A);
    }

    public static function countFixable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_var("SELECT COUNT(*) FROM $tableName WHERE canBeFixed = 1");
    }

    public static function countAll()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_var("SELECT COUNT(*) FROM $tableName");
    }

    public static function setToBeFixed($wrongPostId, $slug)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ['canBeFixed' => 1], ['postId' => $wrongPostId, 'slugPost' => $slug]);
    }

    public static function update($id, $args)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, $args, ['ID' => $id]);
    }

    public static function getUrlsFixable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE canBeFixed = 1", ARRAY_A);
    }

    public static function deleteById($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->delete($tableName, ['ID' => $id]);
    }

    public static function create($args)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->insert($tableName, $args);
        return $wpdb->insert_id;
    }

    public static function loadByPostIds($postId, $notTranslatedPostId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE postId = $postId AND notTranslatedPostId = $notTranslatedPostId", ARRAY_A);
    }
}


