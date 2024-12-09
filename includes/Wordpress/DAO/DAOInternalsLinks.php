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
            `hasBeenFixed` TINYINT NOT NULL,
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
        return $wpdb->get_var("SELECT COUNT(*) FROM $tableName WHERE canBeFixed = 1 AND hasBeenFixed = 0");
    }

    public static function countAll()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_var("SELECT COUNT(*) FROM $tableName");
    }

    public static function countFixed()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_var("SELECT COUNT(*) FROM $tableName WHERE hasBeenFixed = 1");
    }

    public static function setToBeFixed($notTranslatedPostId, $slugPost)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ['canBeFixed' => 1], ['notTranslatedPostId' => $notTranslatedPostId, 'slugPost' => $slugPost]);
    }

    public static function update($id, $args)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, $args, ['ID' => $id]);
    }

    public static function deleteById($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->delete($tableName, ['ID' => $id]);
    }

    public static function reset()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->query("TRUNCATE TABLE $tableName");
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

    public static function getPostsCron($lastPostId = 0)
    {
        global $wpdb;

        return $wpdb->get_results("SELECT posts.ID, posts.post_content FROM $wpdb->posts posts
                    WHERE 
                        posts.post_type IN ('page', 'post', 'elementor_library') AND
                        posts.post_status IN ('draft', 'publish', 'future', 'private', 'pending') AND 
                        posts.ID > $lastPostId
                    ORDER BY posts.ID ASC 
                    LIMIT 10
                    ");
    }

    public static function getPostsCronTotal($lastPostId = 0)
    {
        global $wpdb;

        return $wpdb->get_var("SELECT COUNT(*) FROM $wpdb->posts posts
                    WHERE 
                        posts.post_type IN ('page', 'post', 'elementor_library') AND
                        posts.post_status IN ('draft', 'publish', 'future', 'private', 'pending') AND
                        posts.ID > $lastPostId
                    ");
    }

    public static function getFixable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT * FROM $tableName WHERE canBeFixed = 1 AND hasBeenFixed = 0", ARRAY_A);
    }
}
