<?php

namespace TraduireSansMigraine\Wordpress\DAO;

class DAOActions
{

    public static $ORIGINS = ['QUEUE' => 0, 'EDITOR' => 1000];
    public static $STATE = [
        'PENDING' => 'PENDING',
        'PROCESSING' => 'PROCESSING',
        'DONE' => 'DONE',
        'ERROR' => 'ERROR',
        'PAUSE' => 'PAUSE',
        'ARCHIVED' => 'ARCHIVED',
    ];
    private static $TABLE_NAME = "tsm_actions";
    private static $instance = null;
    private $currentVersion;
    private $optionVersion = "tsm_actions_db_version";

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

    public static function deleteTable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS $tableName");
    }

    /**
     * @param $postId int
     * @param $slugTo string
     * @param $origin string
     * @return int
     */
    public static function createAction($postId, $slugTo, $origin)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->insert($tableName, [
            'postId' => $postId,
            'slugTo' => $slugTo,
            'state' => self::$STATE["PENDING"],
            'origin' => $origin,
            'createdAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ]);
        return $wpdb->insert_id;
    }

    public static function updateAction($id, $data)
    {
        global $wpdb;

        $where = ['ID' => $id];
        if (isset($data["lock"])) {
            $where["lock"] = NULL;
        }
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, array_merge($data, ["updatedAt" => date('Y-m-d') . 'T' . date('H:i:s') . 'Z']), $where);
    }

    public static function getActionByToken($tokenId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE tokenId = $tokenId", ARRAY_A);
    }

    public static function getActionByPostId($postId, $slugTo)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE postId = $postId AND slugTo = '$slugTo' AND state != '" . self::$STATE["ARCHIVED"] . "' ORDER BY ID DESC LIMIT 1", ARRAY_A);
    }

    public static function getActionsByPostId($postId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT * FROM $tableName WHERE ID IN (SELECT MAX(ID) FROM $tableName WHERE postId = $postId GROUP BY slugTo)", ARRAY_A);
    }

    public static function getActionPaused()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE state = '" . self::$STATE["PAUSE"] . "' ORDER BY ID DESC LIMIT 1", ARRAY_A);
    }

    public static function get($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE ID = $id", ARRAY_A);
    }

    public static function getActionsForQueue()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT actions.*, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM wp_term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap  FROM $tableName actions
                        LEFT JOIN $wpdb->posts posts ON posts.ID = actions.postId
                        WHERE actions.state != '" . self::$STATE["ARCHIVED"] . "' 
                         ORDER BY actions.origin DESC, actions.ID ASC", ARRAY_A);
    }

    public static function removeAction($itemId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $deletePending = $wpdb->delete($tableName, ['ID' => $itemId, 'state' => self::$STATE["PENDING"]]);
        if ($deletePending) {
            return $deletePending;
        }
        return $wpdb->delete($tableName, ['ID' => $itemId, 'state' => self::$STATE["PAUSE"]]);
    }

    public static function getNextOrCurrentAction()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE state IN ('" . self::$STATE["PENDING"] . "', '" . self::$STATE["PAUSE"] . "', '" . self::$STATE["PROCESSING"] . "') ORDER BY origin DESC, ID ASC LIMIT 1", ARRAY_A);
    }

    public static function releaseLock($id, $lock)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ['lock' => NULL], ['ID' => $id, 'lock' => $lock]);
    }

    public static function setAsArchivedAllDoneActions()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ['state' => self::$STATE["ARCHIVED"]], ['state' => self::$STATE["DONE"]]);
    }
}


