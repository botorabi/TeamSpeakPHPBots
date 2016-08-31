<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\db;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;
use PDO;
use PDOException;

/**
 * Database handler providing low-level functionality for accessing data.
 * The db account information must be provided in "config/Configuration.php".
 * 
 * @package   com\tsphpbots\db
 * @created   22th June 2016
 * @author    Botorabi
 */
class DB {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "DB";

    /**
     * @var PDO Database object
     */
    protected static $dbh = null;

    /**
     * Try to connect the database with the access data provided in "config/Configuration.php".
     * The connection will be persistent. If the connection is already established then just return true.
     * 
     * @return  true if the connection was successful, otherwise false
     */
    public static function connect() {

        if (self::$dbh) {
            return true;
        }

        $options = [PDO::ATTR_PERSISTENT => true];
        $url  = "mysql:host=" . Config::getDB("host") . ";";
        $url .= "port=" .       Config::getDB("port") . ";";
        $url .= "dbname=" .     Config::getDB("dbName") . ";";
        $url .= "charset=utf8";

        try {
            self::$dbh = new PDO($url, Config::getDB("userName"), Config::getDB("password"), $options);
        }
        catch(PDOException $e) {
            Log::error(self::$TAG, "Cannot connect the database, reason: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Try to disconnect from database server.
     * 
     * NOTE: The actual disconnect happens when all references of retrieved data
     * are no longer alive (see http://php.net/manual/de/pdo.connections.php).
     * 
     * @return       true if successful, otherwise false
     */
    public static function disconnect() {
        self::$dbh = null;
        return true;
    }

    /**
     * Prepare a statement for the given SQL.
     * 
     * @param $sql      SLQ which is prepared
     * @return          Statement object
     * @throws          Exception or PDOException
     */
    public static function prepareStatement($sql) {
        if (self::$dbh == null) {
            throw new \Exception("Cannot prepare SQL, no database connection exists!");
        }
        return self::$dbh->prepare($sql); 
    }

    /**
     * Execute the given statement and do an automatic connection recovery if it was lost.
     * 
     * @param Object $statement     PDO statement
     * @return boolean              Return true if successful, otherwise false.
     */
    public static function executeStatement($statement) {
        $statement->execute();
        $res = $statement->errorInfo();
        if (strcmp($res[0], "00000")) {
            // check if the server connection was lost, if so try to recover
            if ((strcmp($res[1], "2006") === 0 ) || (strcmp($res[1], "2013") === 0)) {
                Log::debug(self::$TAG, "lost connection to database, try to reconnect");
                // reconnect to database
                self::disconnect();
                self::connect();
                $statement->execute();
                $res = $statement->errorInfo();
                if (strcmp($res[0], "00000")) {
                    Log::warning(self::$TAG, " database errorinfo: " . print_r($res, true));
                    return false;
                }
            }
            else {
                Log::warning(self::$TAG, "database errorinfo: " . print_r($res, true));
                return false;
            }
        }
        return true;
    }

    /**
     * Return the ID used for the last table raw creation (so far its ID was an auto-generated).
     * 
     * @return  Last created ID used for a new table
     * @throws  Exception or PDOException
     */
    public static function getLastInsertId() {
        if (self::$dbh == null) {
            throw new \Exception("Cannot get last inserted ID, no database connection exists!");
        }
        return self::$dbh->lastInsertId();
    }

    /**
     * Return all found objects as field arrays. If the table could not be found
     * then null is returned.
     * 
     * @param $tableName    The name of database table
     * @param $filter       Optional array of field/value pair which is used as AND-filter.
     * @return              All found objects (can also be empty), or null if the table does not exist
     *                      or there is no connection to database.
     */
    public static function getObjects($tableName, array $filter = null) {
        
        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot get database objects!");
            return null;
        }

        $objects = [];
        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            if (!$filter) {
                $stmt = DB::prepareStatement("SELECT * FROM " . $fulltablename);
            }
            else {
                $closure = "";
                // define the query parameters
                foreach($filter as $key => $value) {
                    if (strlen($closure) > 0) {
                        $closure .= " AND ";
                    }
                    $closure .= $key . " = " . ":" . $key;
                }
                $stmt = DB::prepareStatement("SELECT * FROM " . $fulltablename . " WHERE " . $closure);
                // bind the values
                foreach($filter as $key => $value) {
                    $stmt->bindValue(":" . $key, $value);
                }
            }

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "getObjects, could not perform database operation!");
                return null;
            }

            $rawdata = $stmt->fetchAll();
            foreach($rawdata as $raw) {
                foreach($raw as $key => $value) {
                    if (!is_numeric($key)) {
                        $data[$key] = $value;
                    }
                }
                $objects[] = $data;
            }
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while getting table raws, reason: " . $e->getMessage());
            return null;
        }
        return $objects;
    }

    /**
     * Return all found object IDs. If the table could not be found then null is returned.
     * 
     * @param $tableName    The name of database table
     * @return              All found object IDs (can also be empty), or null if the table does not exist
     *                      or there is no connection to database.
     */
    public static function getObjectIDs($tableName) {
        
        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot get database objects IDs!");
            return null;
        }

        $ids = [];
        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            $stmt = DB::prepareStatement("SELECT id FROM " . $fulltablename);

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "getAllObjectIDs, could not perform database operation!");
                return null;
            }

            $rawdata = $stmt->fetchAll();
            foreach($rawdata as $raw) {
                $ids[] = $raw["id"];
            }
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while getting table raws, reason: " . $e->getMessage());
            return null;
        }
        return $ids;
    }

    /**
     * Return count of all found objects. If the table could not be found then null is returned.
     * 
     * @param $tableName    The name of database table
     * @return              Count of found objects, or null if the table does not exist
     *                      or there is no connection to database.
     */
    public static function getObjectCount($tableName) {
        
        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot get database objects count!");
            return null;
        }

        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            $stmt = DB::prepareStatement("SELECT COUNT(*) FROM " . $fulltablename);

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "getObjectCount, could not perform database operation!");
                return null;
            }

            $cnt = $stmt->fetchAll()[0][0];
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while getting table raws, reason: " . $e->getMessage());
            return null;
        }
        return $cnt;
    }

    /**
     * Create a new object (table raw) and return its ID if successful.
     * 
     * @param $tableName        The name of database table
     * @param $fields           Raw fields, array of tuples [field name => value]
     *                          NOTE: If a value is an array then it gets converted to a
     *                                comma separated string array.
     * @return                  Object ID if successful, otherwise null.
     */
    public static function createObject($tableName, array $fields) {

        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot create database object!");
            return null;
        }

        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            $sql = "INSERT INTO " . $fulltablename . "(";
            $params = "";
            $values = "";
            foreach($fields as $key => $value) {
                if (strlen($params) > 0) {
                    $params .= ",";
                    $values .= ",";
                }
                $params .= $key;
                $values .= ":" . $key;
            }
            $sql .= $params . ") VALUES(" . $values . ")";
            $stmt = DB::prepareStatement($sql); 
            
            // bind the values
            foreach($fields as $key => $value) {
                $stmt->bindValue(":" . $key, self::encodeFieldValue($value));
            }

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "createObject, could not perform database operation!");
                return null;
            }

            $userid = DB::getLastInsertId();
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while getting table raws, reason: " . $e->getMessage());
            return null;
        }
        return $userid;
    }

    /**
     * Store back the object changes to databank. Use $fields for updating only given
     * fields, otherwise all fields are updated.
     *
     * @param $tableName   The name of database table
     * @param $id          Object ID (table raw ID)
     * @param $fields      Field values which are updated, an array of 
     *                      tuples [field name -> value].
     * @return             true if successful, otherwise false
     */
    public static function updateObject($tableName, $id, array $fields) {

        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot update database object!");
            return null;
        }
 
        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            $sql = "UPDATE " . $fulltablename . " SET ";
            $params = "";
            foreach($fields as $key => $value) {
                if (strlen($params) > 0) {
                    $params .= ",";
                }
                $params .= $key . " = " . ":" . $key;
            }
            $sql .= $params . " WHERE id = " . $id;
            $stmt = DB::prepareStatement($sql); 
            // bind the values
            foreach($fields as $key => $value) {
                $stmt->bindValue(":" . $key, self::encodeFieldValue($value));
            }

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "updateObject, could not perform database operation!");
                return false;
            }
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while updating table raws, reason: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Delete the object with given ID from a database table.
     * 
     * @param $tableName    The name of database table
     * @param $id           Object ID
     * @return              true if successful, otherwise false.
     */
    public static function deleteObject($tableName, $id) {
        
        if (!DB::connect()) {
            Log::warning(self::$TAG, "Cannot connect database, cannot delete database object!");
            return false;
        }

        try {
            $fulltablename = Config::getDB("dbName") . "." . $tableName;
            $sql = "DELETE FROM " . $fulltablename . " WHERE id=" . $id;
            $stmt = DB::prepareStatement($sql); 

            if (self::executeStatement($stmt) === false) {
                Log::warning(self::$TAG, "deleteObject, could not perform database operation!");
                return false;
            }
        }
        catch (PDOException $e) {
            Log::warning(self::$TAG, "Problem occured while deleting table raw, reason: " . $e->getMessage());
            return false;
        }
        return true;
    }

    /**
     * Encode a field value ready for storing into database.
     * If the value is an array then it will get converted to a 
     * comma separated value string, otherwise the original value is returned.
     *
     * @param $value    Field value
     * @return          Encoded field value
     */
    static protected function encodeFieldValue($value) {

        if (is_array($value) === true) {
            $valuestr = "";
            foreach($value as $v) {
                if (strlen($valuestr) > 0) {
                    $valuestr .= ",";
                }
                $valuestr .= $v;
            }
            $value = $valuestr;
        }
        return $value;
    }
}
