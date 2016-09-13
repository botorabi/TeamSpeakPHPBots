<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\db;
use com\tsphpbots\utils\Log;
use PDO;
use PDOException;

/**
 * This class provides functionality for connecting a database and executing
 * SQL statements.
 * 
 * @package   com\tsphpbots\db
 * @created   5th September 2016
 * @author    Botorabi
 */
class DBConnection {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "DBConnection";

    /**
     * @var PDO Database object
     */
    protected $dbh = null;

    /**
     * @var string  Connection URL
     */
    protected $url = null;

    /**
     * @var string  User name
     */
    protected $userName = null;

    /**
     * @var string  User password
     */
    protected $userPW = null;

    /**
     * Try to connect the database with the provided access data.
     * The connection will be persistent. If the connection is already established then just return the handler.
     * 
     * @param string $host          Connection host
     * @param int $port             Connection port
     * @param string $dbName        DB name
     * @param string $userName      DB user name
     * @param string $userPW        DB user password
     * @return Object               Return the databank handler if the connection was successful, otherwise null.
     */
    public function connect($host, $port, $dbName, $userName, $userPW) {
        if (!is_null($this->dbh)) {
            return $this->dbh;
        }

        $url  = "mysql:host=" . $host . ";";
        $url .= "port=" .       $port . ";";
        $url .= "dbname=" .     $dbName . ";";
        $url .= "charset=utf8";

        // store the information for a possible reconnecting attempt later
        $this->url = $url;
        $this->userName = $userName;
        $this->userPW = $userPW;

        $this->dbh = $this->connectDB($url, $userName, $userPW);

        return $this->dbh;
    }

    /**
     * Try to disconnect from database server.
     * 
     * NOTE: The actual disconnect happens when all references of retrieved data
     * are no longer alive (see http://php.net/manual/de/pdo.connections.php).
     * 
     * @return boolean  true if successful, otherwise false
     */
    public function disconnect() {
        if (is_null($this->dbh)) {
            return false;
        }
        $this->dbh = null;
        return true;
    }

    /**
     * Establish a database connection, this is used for automatic connection loss recovery.
     * 
     * @param string $url           DB connection URL
     * @param string $userName      DB user name
     * @param string $userPW        DB user password
     * @return Object               Database handler if successful, otherwise null.
     */
    protected function connectDB($url, $userName, $userPW) {
        $options = [PDO::ATTR_PERSISTENT => true];
        try {
            $dbh = new PDO($url, $userName, $userPW, $options);
            return $dbh;
        }
        catch(PDOException $e) {
            Log::error(self::$TAG, "Cannot connect the database, reason: " . $e->getMessage());
            return null;
        }
    }

    /**
     * Prepare a statement for the given SQL.
     * 
     * @param $sql      SLQ which is prepared
     * @return          Statement object
     * @throws          Exception or PDOException
     */
    public function prepareStatement($sql) {
        if (is_null($this->dbh)) {
            throw new \Exception("Cannot prepare SQL, no database connection exists!");
        }
        return $this->dbh->prepare($sql); 
    }

    /**
     * Execute a statement given its creation function and do an automatic connection recovery if it was lost.
     * The reason for passing a statement creator function instead of a statement is the automatic connection
     * loss recovery.
     * 
     * @param Function $fcnStatementCreator     A function creating a PDO statement
     * @return Object                           Return the PDO statement if successfull, otherwise null.
     */
    public function executeStatement($fcnStatementCreator) {
        if (!is_callable($fcnStatementCreator)) {
            Log::error(self::$TAG, "executeStatement needs a callable function for statement creation!");
            return null;
        }
        $statement = $fcnStatementCreator();
        @$statement->execute();
        $res = $statement->errorInfo();
        if (strcmp($res[0], "00000")) {
            // check if the server connection was lost, if so try to recover
            if ((strcmp($res[1], "2006") === 0 ) || (strcmp($res[1], "2013") === 0)) {
                Log::debug(self::$TAG, "lost connection to database, try to reconnect");
                // try to reconnect the database
                $this->disconnect();
                $this->dbh = $this->connectDB($this->url, $this->userName, $this->userPW);
                if (is_null($this->dbh)) {
                    Log::warning(self::$TAG, " could not recover the connection to database!");
                    return null;
                }
                $statement = $fcnStatementCreator();
                $statement->execute();
                $res = $statement->errorInfo();
                if (strcmp($res[0], "00000")) {
                    Log::warning(self::$TAG, " database errorinfo: " . print_r($res, true));
                    return null;
                }
                Log::debug(self::$TAG, " connection to database succesfully recovered");
            }
            else {
                Log::warning(self::$TAG, "database errorinfo: " . print_r($res, true));
                return null;
            }
        }
        return $statement;
    }

    /**
     * Return the ID used for the last table raw creation (so far its ID was an auto-generated).
     * 
     * @return  Last created ID used for a new table
     * @throws  Exception or PDOException
     */
    public function getLastInsertId() {
        if (is_null($this->dbh)) {
            throw new \Exception("Cannot get last inserted ID, no database connection exists!");
        }
        return $this->dbh->lastInsertId();
    }
}