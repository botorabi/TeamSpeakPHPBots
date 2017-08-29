<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\bots\chatbot;
use com\tsphpbots\config\Config;
use com\tsphpbots\db\DBObject;


/**
 * Data model for chat bot
 * 
 * @created:  4nd August 2016
 * @author:   Botorabi (boto)
 */
class ChatBotModel extends DBObject {

    protected static $TAG = "ChatBotModel";

    /**
     * @var string Bot config table name
     */
    private static $DB_TABLE_NAME_BOT = "chatbot";

    /**
     * @var string Bot type
     */
    private static $BOT_TYPE_NAME = "ChatBot";

    /**
     * Setup the object fields. Note that a field called "id" is automatically
     * created for the object, no need to define it here!
     * 
     * @implementes DBObject
     *
     *   botType                        The type of this bot (see $BOT_TYPE_NAME above) 
     *   name                           Bot name
     *   description                    A short bot description
     *   active                         Enable/disable the bot (1/0)
     *   nickName                       Bot's nick name
     *   channelID                      Channel ID, the bot will reside in this channel
     */
    public function setupFields() {
        $this->objectFields["botType"]       = self::$BOT_TYPE_NAME;
        $this->objectFields["name"]          = "";
        $this->objectFields["description"]   = "";
        $this->objectFields["active"]        = 0;
        $this->objectFields["nickName"]      = "";
        $this->objectFields["channelID"]     = 0;
        $this->objectFields["greetingText"]  = "";
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
    public static function getTableName() {
        return Config::getDB("tablePrefix") . self::$DB_TABLE_NAME_BOT;
    }
}
