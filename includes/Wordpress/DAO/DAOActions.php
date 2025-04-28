<?php

namespace TraduireSansMigraine\Wordpress\DAO;

class DAOActions
{

    public static $ORIGINS = ['QUEUE' => 0, 'EDITOR' => 1000];
    public static $STATE = [
        'CREATING' => 'CREATING',
        'PENDING' => 'PENDING',
        'PROCESSING' => 'PROCESSING',
        'DONE' => 'DONE',
        'ERROR' => 'ERROR',
        'PAUSE' => 'PAUSE',
        'ARCHIVED' => 'ARCHIVED',
    ];
    public static $ACTION_TYPE = [
        'POST_PAGE' => 'POST_PAGE',
        'PRODUCT' => 'PRODUCT',
        'EMAIL' => 'EMAIL',
        'MODEL_ELEMENTOR' => 'MODEL_ELEMENTOR',
        'TERMS' => 'TERMS',
        'ATTRIBUTES' => 'ATTRIBUTE',
    ];
    private static $TABLE_NAME = "tsm_actions";
    private static $instance = null;
    private $currentVersion;
    private $optionVersion = "tsm_actions_db_version";

    public function __construct()
    {
        $this->currentVersion = get_site_option($this->optionVersion);
    }

    public static function updateDatabaseIfNeeded()
    {
        $instance = self::getInstance();
        if ($instance->needUpdateDatabase()) {
            $instance->updateDatabase();
        } else {
            $instance->updateDatabaseVersion220();
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
        $this->updateDatabaseVersion220();
        $this->updateDatabaseVersion230();
        $this->updateDatabaseVersion235();
        update_option($this->optionVersion, TSM__VERSION, true);
    }

    private function installDatabaseVersion200()
    {
        if (version_compare($this->currentVersion, '2.0.0') >= 0) {
            return;
        }
        global $wpdb;
        $stateEnum = "";
        foreach (
            [
                'PENDING' => 'PENDING',
                'PROCESSING' => 'PROCESSING',
                'DONE' => 'DONE',
                'ERROR' => 'ERROR',
                'PAUSE' => 'PAUSE',
                'ARCHIVED' => 'ARCHIVED'
            ] as $state
        ) {
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

    private function updateDatabaseVersion220()
    {
        if (version_compare($this->currentVersion, '2.2.0') >= 0) {
            return;
        }
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "ALTER TABLE $tableName ADD COLUMN `estimatedQuota` INT NULL";
        $wpdb->query($sql);
    }

    private function updateDatabaseVersion230()
    {
        if (version_compare($this->currentVersion, '2.3.0') >= 0) {
            return;
        }
        global $wpdb;
        $stateEnum = "";
        foreach (
            [
                'CREATING' => 'CREATING',
                'PENDING' => 'PENDING',
                'PROCESSING' => 'PROCESSING',
                'DONE' => 'DONE',
                'ERROR' => 'ERROR',
                'PAUSE' => 'PAUSE',
                'ARCHIVED' => 'ARCHIVED',
                'ARCHIVED_ERROR' => 'ARCHIVED_ERROR'
            ] as $state
        ) {
            if ($stateEnum !== "") {
                $stateEnum .= ",";
            }
            $stateEnum .= "'$state'";
        }
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "ALTER TABLE $tableName ADD COLUMN `actionType` VARCHAR(255) NOT NULL DEFAULT '" . self::$ACTION_TYPE["POST_PAGE"] . "'";
        $wpdb->query($sql);
        $sql = "ALTER TABLE $tableName ADD COLUMN `actionParent` INT NULL";
        $wpdb->query($sql);
        $sql = "ALTER TABLE $tableName CHANGE `postId` `objectId` VARCHAR(255) NOT NULL";
        $wpdb->query($sql);
        $sql = "ALTER TABLE $tableName ADD COLUMN `objectIdTranslated` VARCHAR(255) NULL";
        $wpdb->query($sql);
        $sql = "ALTER TABLE $tableName MODIFY COLUMN `state` ENUM($stateEnum) NOT NULL";
        $wpdb->query($sql);
    }

    private function updateDatabaseVersion235()
    {
        if (version_compare($this->currentVersion, '2.3.5') >= 0) {
            return;
        }
        global $wpdb;
        $stateEnum = "";
        foreach (
            [
                'CREATING' => 'CREATING',
                'PENDING' => 'PENDING',
                'PROCESSING' => 'PROCESSING',
                'DONE' => 'DONE',
                'ERROR' => 'ERROR',
                'PAUSE' => 'PAUSE',
                'ARCHIVED' => 'ARCHIVED',
                'ARCHIVED_ERROR' => 'ARCHIVED_ERROR'
            ] as $state
        ) {
            if ($stateEnum !== "") {
                $stateEnum .= ",";
            }
            $stateEnum .= "'$state'";
        }
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "ALTER TABLE $tableName MODIFY COLUMN `state` ENUM($stateEnum) NOT NULL";
        $wpdb->query($sql);
    }

    public static function deleteTable()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->query("DROP TABLE IF EXISTS $tableName");
    }

    public static function createAction($objectId, $slugTo, $origin, $estimatedQuota, $actionType, $actionParent = null)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->insert($tableName, [
            'objectId' => $objectId,
            'slugTo' => $slugTo,
            'state' => self::$STATE["CREATING"],
            'origin' => $origin,
            'createdAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
            'estimatedQuota' => $estimatedQuota,
            'actionType' => $actionType,
            'actionParent' => $actionParent
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

    public static function updateStateClonedActionsPending($state, $objectId, $slugTo)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ["updatedAt" => date('Y-m-d') . 'T' . date('H:i:s') . 'Z', 'state' => $state], ['objectId' => $objectId, 'slugTo' => $slugTo, 'state' => DAOActions::$STATE["PENDING"]]);
    }

    public static function updateStateChildrenActions($state, $actionParent)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, ["updatedAt" => date('Y-m-d') . 'T' . date('H:i:s') . 'Z', 'state' => $state], ['actionParent' => $actionParent]);
    }


    public static function getActionByToken($tokenId)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE tokenId = %d",
            $tokenId
        );
        return $wpdb->get_row($preparedQuery, ARRAY_A);
    }

    public static function getActionByObjectId($objectId, $slugTo, $actionType)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE actionType = %s AND objectId = %s AND slugTo = %s AND state != %s ORDER BY ID DESC LIMIT 1",
            $actionType,
            $objectId,
            $slugTo,
            self::$STATE["ARCHIVED"]
        );
        return $wpdb->get_row($preparedQuery, ARRAY_A);
    }

    public static function getActionsByObjectId($objectId, $actionType)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE ID IN (SELECT MAX(ID) FROM $tableName WHERE objectId = %s AND actionType = %s GROUP BY slugTo)",
            $objectId,
            $actionType
        );
        return $wpdb->get_results($preparedQuery, ARRAY_A);
    }

    public static function getActionPaused()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE state = %s ORDER BY ID DESC LIMIT 1",
            self::$STATE["PAUSE"]
        );
        return $wpdb->get_row($preparedQuery, ARRAY_A);
    }

    public static function get($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE ID = %d",
            $id
        );
        return $wpdb->get_row($preparedQuery, ARRAY_A);
    }

    public static function getActionsForQueue()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE state NOT IN (%s, %s) AND actionParent IS NULL ORDER BY origin DESC, ID",
            self::$STATE["ARCHIVED"],
            self::$STATE["CREATING"]
        );
        return $wpdb->get_results($preparedQuery, ARRAY_A);
    }

    public static function getNextOrCurrentAction()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE state IN (%s, %s, %s) AND actionParent IS NULL ORDER BY origin DESC, ID LIMIT 1",
            self::$STATE["PENDING"],
            self::$STATE["PAUSE"],
            self::$STATE["PROCESSING"]
        );
        return $wpdb->get_row($preparedQuery, ARRAY_A);
    }

    public static function removeAction($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->delete($tableName, ['ID' => $id]);
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
        $wpdb->update($tableName, ['state' => self::$STATE["ARCHIVED_ERROR"]], ['state' => self::$STATE["ERROR"]]);
    }

    public static function getChildren($id)
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $preparedQuery = $wpdb->prepare(
            "SELECT * FROM $tableName WHERE actionParent = %d",
            $id
        );
        return $wpdb->get_results($preparedQuery, ARRAY_A);
    }

    public static function cleanQueue()
    {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;

        $preparedQuery = $wpdb->prepare(
            "DELETE FROM $tableName WHERE state NOT IN (%s, %s, %s)",
            self::$STATE["PENDING"],
            self::$STATE["PAUSE"],
            self::$STATE["PROCESSING"]
        );

        $wpdb->query($preparedQuery);
    }
}
