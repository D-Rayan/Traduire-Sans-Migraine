<?php

namespace TraduireSansMigraine\Wordpress\DAO;

class DAOQueue {

    private $currentVersion;
    private static $TABLE_NAME = "tsm_queue";

    public static $ORIGINS = ['QUEUE' => 'QUEUE', 'EDITOR' => 'EDITOR'];
    public static $STATE = [
        'PENDING' => 'PENDING',
        'PROCESSING' => 'PROCESSING',
        'DONE' => 'DONE',
        'ERROR' => 'ERROR',
        'PAUSE' => 'PAUSE',
        'ARCHIVED' => 'ARCHIVED',
    ];
    private $optionVersion = "queue_tsm_db_version";

    public function __construct()
    {
        $this->currentVersion = get_site_option($this->optionVersion) || '0.0.0';
    }

    public function needUpdateDatabase()
    {
        return version_compare($this->currentVersion, TSM__VERSION) < 0;
    }
    public function updateDatabase()
    {
        $this->installDatabaseVersion200();
        update_option($this->optionVersion, TSM__VERSION,  true);
    }
    private function installDatabaseVersion200() {
        if (version_compare($this->currentVersion, '2.0.0') >= 0) {
            return;
        }
        global $wpdb;
        $charsetCollate = $wpdb->get_charset_collate();
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $sql = "CREATE TABLE $tableName (
            `ID` INT NOT NULL AUTO_INCREMENT,
            `postId` BIGINT NOT NULL,
            `tokenId` VARCHAR(255) NOT NULL,
            `slugTo` VARCHAR(3) NOT NULL,
            `state` ENUM('PENDING','PROCESSING','DONE','ERROR','PAUSE', 'ARCHIVED') NOT NULL,
            `origin` ENUM('QUEUE','EDITOR') NOT NULL,
            `response` JSON NULL,
            `createdAt` VARCHAR(20) NOT NULL, 
            `updatedAt` VARCHAR(20) NOT NULL,
            PRIMARY KEY (`ID`)
        ) $charsetCollate;";

        require_once ABSPATH . 'wp-admin/includes/upgrade.php';
        dbDelta($sql);
    }

    private static $instance = null;
    public static function getInstance() {
        if (self::$instance === null) {
            self::$instance = new static();
        }
        return self::$instance;
    }

    public static function init() {
        $instance = self::getInstance();
        if ($instance->needUpdateDatabase()) {
            $instance->updateDatabase();
        }
    }

    /**
     * @param $postId int
     * @param $slugTo string
     * @param $origin string
     * @return int
     */
    public static function addToQueue($postId, $slugTo, $origin) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->insert($tableName, [
            'postId' => $postId,
            'slugTo' => $slugTo,
            'state' => 'PENDING',
            'origin' => $origin,
            'createdAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ]);
        return $wpdb->insert_id;
    }

    public static function setItemAsProcessing($id) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, [
            'state' => "PROCESSING",
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ], ['ID' => $id]);
    }

    public static function setItemAsDone($id, $response) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, [
            'state' => "DONE",
            'response' => json_encode($response),
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ], ['ID' => $id]);
    }

    public static function setItemAsError($id, $response) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, [
            'state' => "ERROR",
            'response' => json_encode($response),
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ], ['ID' => $id]);
    }

    public static function setItemAsPause($id, $response) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        $wpdb->update($tableName, [
            'state' => "PAUSE",
            'response' => json_encode($response),
            'updatedAt' => date('Y-m-d') . 'T' . date('H:i:s') . 'Z',
        ], ['ID' => $id]);
    }

    public static function getItemByToken($tokenId) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE tokenId = $tokenId");
    }

    public static function getItemByPostId($postId, $slugTo) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE postId = $postId AND slugTo = '$slugTo' ORDER BY ID DESC LIMIT 1");
    }

    public static function get($id) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE ID = $id");
    }

    public static function getItemsForQueue() {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT queue.*, posts.post_title, posts.post_author, posts.post_status, posts.post_modified, (SELECT trTaxonomyTo.description FROM $wpdb->term_taxonomy trTaxonomyTo WHERE 
                                trTaxonomyTo.taxonomy = 'post_translations' AND 
                                trTaxonomyTo.term_taxonomy_id IN (
                                    SELECT trTo.term_taxonomy_id FROM wp_term_relationships trTo WHERE trTo.object_id = posts.ID
                                )
                            ) AS translationMap  FROM $tableName queue
                        LEFT JOIN $wpdb->posts posts ON posts.ID = queue.postId
                        WHERE queue.origin = '". self::$ORIGINS["QUEUE"] ."' AND queue.state != 'ARCHIVED' 
                        ORDER BY queue.ID DESC", ARRAY_A);
    }

    public static function countItemsQueueThatWillBeProcessed() {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT COUNT(*) FROM $tableName WHERE origin = '". self::$ORIGINS["QUEUE"] ."' AND state IN ('PENDING', 'PAUSE', 'PROCESSING')");
    }

    public static function countItemsQueueError() {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_results("SELECT COUNT(*) FROM $tableName WHERE origin = '". self::$ORIGINS["QUEUE"] ."' AND state = 'ERROR'");
    }

    public static function removeItem($itemId) {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->delete($tableName, ['ID' => $itemId, 'state' => 'PENDING']);
    }

    public static function getNextItem() {
        global $wpdb;
        $tableName = $wpdb->prefix . self::$TABLE_NAME;
        return $wpdb->get_row("SELECT * FROM $tableName WHERE state IN ('PENDING', 'PAUSE', 'PROCESSING') AND origin = '". self::$ORIGINS["QUEUE"] ."' ORDER BY ID ASC LIMIT 1");
    }
}


