<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\bots;
use com\tsphpbots\bots\BotBase;
require_once(__DIR__ . '/TestBotModel.php');

/**
 * A bot class for testing framework's BaseBot
 * 
 * @created:  6nd August 2016
 * @author:   Botorabi (boto)
 */
class TestBot extends BotBase {

    /**
     * Construct the bot manager.
     * 
     * @param  Object $server   TS3 server object
     * @throws Exception        Throws exception if the given server is invalid.
     */
    public function __construct() {
      parent::__construct();
      $this->model = new TestBotModel;  
    }

    /**
     * Get all available bot IDs.
     * This is used by bot manager for loading all available bots from database.
     * 
     * @return array    Array of all available bot IDs, or null if there is no corresponding table in database.
     */
    static public function getAllIDs() {
        return TestBotModel::getAllObjectIDs();
    }

    /**
     * Create a new bot instance.
     * 
     * @param Object $server TS3 Server object
     * @return Object        New instance of the bot.
     */
    static public function create() {
        return new TestBot();
    }

    /**
     * Load the bot from database.
     * 
     * @implements base class method
     * 
     * @param int $id       Bot ID (database table row ID)
     * @return boolean      Return false if the object could not be loaded, otherwise true.
     */
    public function loadData($id) {
        if ($this->model->loadObject($id) === false) {
            return false;
        }
        return true;
    }

    /**
     * Initialize the bot. Usually the bot will load its data from database using the given bot ID.
     * This method is used also by the bot manager for creating bots.
     * 
     * @param int $botId    The database ID
     * @return boolean      Return true if the bot was initialized successfully, otherwise false.
     */
    public function initialize() {
        if (is_null($this->ts3Server)) {
            return false;
        }
        return true;
    }

    /**
     * Get the bot type.
     * 
     * @return string       The bot type
     */
    public function getType() {
        return $this->model->botType;
    }

    /**
     * Get the bot name.
     * 
     * @return string       The bot name
     */
    public function getName() {
        return $this->model->name;
    }

    /**
     * A bot may have a database model for persistence. If so then return an
     * instance of the model, or return null if there is no need for persistence.
     * 
     * @return Object  A database model object (expected to be a derived class from DBObject),
     *                 or null if the bot has no database model.
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * Get the unique bot ID.
     * 
     * @return int    The unique bot ID > 0, or 0 if the bot is not initialized yet.
     */
    public function getID() {
        return $this->model->id;
    }

    /**
     * Update the bot.
     */
    public function update() {}

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
