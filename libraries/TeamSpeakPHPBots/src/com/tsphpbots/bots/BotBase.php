<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\bots;

/**
 * Common interface of all bots
 * 
 * @package   com\tsphpbots\bots
 * @created   25th September 2016
 * @author    Botorabi
 */
interface IBotBase  {
    /**
     * Get all available bot IDs.
     * This is used by bot manager for loading all available bots from database.
     * 
     * @return array    Array of all available bot IDs, or null if there is no corresponding table in database.
     */
    public static function getAllIDs();

    /**
     * Create a new bot instance.
     * 
     * @return Object        New instance of the bot.
     */
    public static function create();

    /**
     * Load the bot from database.
     * 
     * @param int $id       Bot ID (database table row ID)
     * @return boolean      Return false if the object could not be loaded, otherwise true.
     */
    public function loadData($id);

    /**
     * Initialize the bot. Usually the bot will have loaded its data from database before.
     * The server connection should also be already in place when this method is called.
     * This method is used also by the bot manager for creating bots.
     * 
     * @return boolean      Return true if the bot was initialized successfully, otherwise false.
     */
    public function initialize();

    /**
     * Get the bot type. This is usually the bot class name.
     * 
     * @return string       The bot type
     */
    public function getType();

    /**
     * Get the bot name.
     * 
     * @return string       The bot name
     */
    public function getName();

    /**
     * A bot may have a database model for persistence. If so then return an
     * instance of the model, or return null if there is no need for persistence.
     * 
     * @return Object  A database model object (expected to be a derived class from DBObject),
     *                 or null if the bot has no database model.
     */
    public function getModel();

    /**
     * Get the unique bot ID.
     * 
     * @return int    The unique bot ID > 0, or 0 if the bot is not initialized yet.
     */
    public function getID();

    /**
     * Update the bot.
     * 
     * @param $deltaTime  Past time in milliseconds since last update
     */
    public function update($deltaTime);
}


/**
 * Base class for all kinds of bots
 * 
 * @package   com\tsphpbots\bots
 * @created   23th July 2016
 * @author    Botorabi
 */
abstract class BotBase implements IBotBase {

    /**
     * @var Object  TS3 server
     */
    protected $ts3Server = null;

    /**
     * Construct the bot manager.
     */
    public function __construct() {}

    /**
     * Set the TS3 server. This is usually set during the bot creation (bot manager's job).
     * 
     * @param Object $server
     */
    public function setServer($server) {
        $this->ts3Server = $server;
    }

    /**
     * Get the stream of server connection. Some bots may need own streams, so the 
     * bot manager uses this method in order to properly distribute connection events etc.
     * 
     * @return Object   Stream object of bot's server connection. Returns null if no server is set.
     */
    public function getServerStream() {
        if (is_null($this->ts3Server)) {
            return null;
        }
        // yeah looks weird, huh?
        return $this->ts3Server->getParent()->getParent()->getTransport()->getStream();
    }

    /**
     * This is a creation policy. A bot can override this method in order to request for an own TS3 server connection.
     * The default is that bots share the same server connection.
     * 
     * @param string $nickName  If an own server connection is needed then the nick name can be set which is used for the connection.
     *                           A default nickname should be provided by caller of this method (usually the bot manager).
     * @return boolean          Return true if an own server connection should be created for the bot.
     */
    public function needsOwnServerConnection(&$nickName) {
        return false;
    }

    /**
     * Call this in order to let the bot know about a shutdown.
     * A concrete bot class can override this method in order to do last chance house-keeping
     * before the bot is shut down.
     */
    public function onShutdown() {}

    /**
     * This method is called whenever a server event was received.
     * Override it in a derived class if it is needed.
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     */
    public function onServerEvent($event, $host) {}

    /**
     * Let the bot know that its configuration was changed (e.g. in the database).
     * Override it in a derived class if it is needed.
     */
    public function onConfigUpdate() {}

    /**
     * Let the bot know about a new message arrived from remote. This can be used
     * for communicating bot specific remote commands received by ServerQuery.
     * Override it in a derived class if it is needed.
     * 
     * @param string $text  Message text, it should not contain any blanks!
     */
    public function onReceivedMessage($text) {}
}