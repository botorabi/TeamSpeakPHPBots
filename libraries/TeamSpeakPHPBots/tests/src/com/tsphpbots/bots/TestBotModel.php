<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\bots;
use com\tsphpbots\config\Config;
use com\tsphpbots\db\DBObject;


/**
 * A data model class for testing framework's BaseBot
 * 
 * @created:  6nd August 2016
 * @author:   Botorabi (boto)
 */
class TestBotModel extends DBObject {

    protected static $TAG = "TestBotModel";

    /**
     * @var string Bot config table name
     */
    private static $DB_TABLE_NAME_BOT = "testbot";

    /**
     * @var string Bot type
     */
    private static $BOT_TYPE_NAME = "TestBot";

    /**
     * Setup the object fields.
     * 
     * @implementes DBObject
     *
     *   botType                        The type of this bot (see $BOT_TYPE_NAME above) 
     *   name                           Bot name
     *   description                    A short bot description
     *   active                         Enable/disable the bot (1/0)
     *   nickName                       Bot's nick name
     */
    public function setupFields() {
        $this->objectFields["id"]            = 0; // for testing we set the ID for tests which do not need the database.
        $this->objectFields["botType"]       = self::$BOT_TYPE_NAME;
        $this->objectFields["name"]          = "";
        $this->objectFields["description"]   = "";
        $this->objectFields["active"]        = 0;
        $this->objectFields["nickName"]      = "";
    }

    /**
     * Load the object fields from database and do the proper preparation.
     * 
     * @override base class method
     * 
     * @param int $id       Object ID
     * @return boolean      true if an object with given ID could be loaded, otherwise false.
     */
    public function loadObject($id) {

        if (DBObject::loadObject($id) === false) {
            return false;
        }
        return true;
    }

    /**
     * Return the table name.
     * 
     * @return string Database table name
     */
    static public function getTableName() {
        return Config::getDB("tablePrefix") . self::$DB_TABLE_NAME_BOT;
    }
}
