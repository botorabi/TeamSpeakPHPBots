<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\bots\greetingbot;
use com\examples\bots\greetingbot\GreetingBotModel;
use com\tsphpbots\bots\BotBase;
use com\tsphpbots\utils\Log;


/**
 * Class for greeting bot
 * 
 * @created:  2nd August 2016
 * @author:   Botorabi (boto)
 */
class GreetingBot extends BotBase {

    /**
     * @var string  This tag is used for logs
     */
    protected static $TAG = "GreetingBot";

    /**
     *
     * @var GreetingBotModel  Database model of the bot. All bot paramerers are hold here.
     */
    protected $model = null;

    /**
     * Construct the bot.
     */
    public function __construct() {
        BotBase::__construct();
        $this->model = new GreetingBotModel;
    }

    /**
     * Get all available bot IDs.
     * This is used by bot manager for loading all available bots from database.
     * 
     * @implements base class method
     * 
     * @return array    Array of all available bot IDs, or null if there is no corresponding table in database.
     */
    public static function getAllIDs() {
        return (new GreetingBotModel)->getAllObjectIDs();
    }

    /**
     * Create a new bot instance.
     * 
     * @implements base class method
     * 
     * @return              New instance of the bot.
     */
    public static function create() {
        return new GreetingBot();
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
        Log::debug(self::$TAG, "loading bot type: " . $this->getType() . ", id " . $id);
        if ($this->model->loadObject($id) === false) {
            Log::warning(self::$TAG, "could not load bot from database: id " . $id);
            return false;
        }
        Log::debug(self::$TAG, " bot succesfully loaded, name: '" . $this->getName() . "'");
        return true;
    }

    /**
     * Initialize the bo.
     * 
     * @implements base class method
     * 
     * @return boolean      Return true if the bot was initialized successfully, otherwise false.
     */
    public function initialize() {
        if (strlen($this->model->greetingText) === 0) {
            Log::warning(self::$TAG, "empty greeting text detected, deactivating the bot!");
            $this->model->active = 0;
        }
        return true;
    }

    /**
     * Get the bot type.
     * 
     * @implements base class method
     * 
     * @return string       The bot type
     */
    public function getType() {
        return $this->model->botType;
    }

    /**
     * Get the bot name.
     * 
     * @implements base class method
     * 
     * @return string       The bot name, may be empty if the bot is still not initialized.
     */
    public function getName() {
        return $this->model->name;
    }

    /**
     * Get the unique bot ID.
     * 
     * @implements base class method
     * 
     * @return int          The unique bot ID > 0, or 0 if the bot is not setup
     *                       or loaded from database yet.
     */
    public function getID() {
        return $this->model->id;
    }

    /**
     * Return the database model.
     * 
     * @implements base class method
     *
     * @return GreetingBotModel  The database model of this bot.
     */
    public function getModel() {
        return $this->model;
    }

    /**
     * The bot configuration was changed.
     * 
     * @implements base class method
     */
    public function onConfigUpdate() {

        // this is just for being on the safe side
        if ($this->getID() > 0) {
            Log::debug(self::$TAG, "reloading bot configuration, type: " . $this->getType() . ", name: " .
                                   $this->getName() . ", id: " . $this->getID());

            $this->loadData($this->getID());
            $this->initialize();
        }
        else {
            Log::warning(self::$TAG, "the bot was not loaded before, cannot handle its config update!");
        }
    }

    /**
     * This method is called whenever a server event was received.
     * 
     * @implements base class method
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     */
    public function onServerEvent($event, $host) {

        // skip updating if the bot is not active
        if ($this->model->active == 0) {
            return;
        }

        if (strcmp($event->getType(), "cliententerview") === 0) {

            $data = $event->getData();
            $clid = $data["clid"];
            $clnick = $data["client_nickname"];

            //Log::verbose(self::$TAG, "going to greet a new client: " . $clnick);

            try {
                // get the client
                $client = $host->serverGetSelected()->clientGetById($clid);
                // assemble the final greeting
                $text = str_replace("<nick>", $clnick, $this->model->greetingText);
                // send the text to the client
                $client->message($text);
            }
            catch (Exception $ex) {
                Log::debug(self::$TAG, "Hmm, I did not have the possibility to greet the new client :-/");
            }
        }
    }

    /**
     * Update the bot.
     *
     * @implements base class method
     */
    public function update($deltaTime) {
        // skip updating if the bot is not active
        if ($this->model->active == 0) {
            return;
        }
    }
}
