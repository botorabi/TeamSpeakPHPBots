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
 * Base class for all kinds of bots
 * 
 * @package   com\tsphpbots\web\controller
 * @created   23th July 2016
 * @author    Botorabi
 */
abstract class BotBase {

    /**
     * @var Object  TS3 server
     */
    protected $ts3Server = null;

    /**
     * Construct the bot manager.
     * 
     * @param  Object $server   TS3 server object
     * @throws Exception        Throws exception if the given server is invalid.
     */
    public function __construct($server) {
      
        if ($server == null) {
            throw new Exception("Invalid TS3 server object!");
        }
        $this->ts3Server = $server;
    }

    /**
     * Get the bot type.
     * 
     * @return string       The bot type
     */
    abstract public function getType();

    /**
     * Get the bot name.
     * 
     * @return string       The bot name
     */
    abstract public function getName();

    /**
     * Create a new bot instance.
     * 
     * @param Object $server TS3 Server object
     * @return Object        New instance of the bot.
     */
    abstract static public function create($server);
   
    /**
     * A bot may have a database model for persistence. If so then return an
     * instance of the model, or return null if there is no need for persistence.
     * 
     * @return Object  A database model object (expected to be a derived class from DBObject),
     *                 or null if the bot has no database model.
     */
    abstract public function getModel();

    /**
     * Get the unique bot ID.
     * 
     * @return int    The unique bot ID > 0, or 0 if the bot is not initialized yet.
     */
    abstract public function getID();

    /**
     * Update the bot.
     */
    abstract public function update();
 
    /**
     * Load the bot from database given its ID. The derived class must implement the functionality
     * which is usually provided by the database model object (DBObject).
     * 
     * @param int $id       The database ID
     * @return boolean      Return false if the object was not loaded, otherwise true.
     *                      Bot types which do not have any database model should return false.
     */
    abstract public function load($id);

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
}