<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\bots;
use com\tsphpbots\utils\Log;

/**
 * This class manages the creation and the lifecycle of bots.
 * 
 * @package   com\tsphpbots\web\controller
 * @created   23th July 2016
 * @author    Botorabi
 */
class BotManager {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "BotManager";

    /**
     * @var array  Registered bot classes (usually a bot type is its class)
     */
    protected $botClasses = [];

    /**
     * @var array  All active bots
     */
    protected $bots = [];

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
     * Register a new bot class. This is usually called during application initialization for
     * bots which wish to have the automatic database and house-keeping functinoality
     * of the BotManager. 
     * 
     * @param string $botClass      The full qualified name (including package path) of the bot class to register.
     *                              For package name separation one can also use forward slashes.
     */
    public function registerBotClass($botClass) {
        $cleanpath = str_replace("/", "\\", $botClass);
        $this->botClasses[] = $cleanpath;
    }

    /**
     * Add a new bot. Check if the bot already exists by proofing its type and ID.
     * 
     * @param Object $bot    The new bot to add
     * @return boolean       Return false if the bot already exists, otherwise true if the bot was added successfully.
     */
    public function addBot($bot) {
        foreach($this->bots as $k => $v) {
            if ((strcmp($v->getType(), $bot->getType()) === 0) && ($v->getID() == $bot->getID())) {
                Log::warning(self::$TAG, "cannot add bot, it already exists: " . $bot->getName());
                return false;
            }
        }
        Log::debug(self::$TAG, "bot was added: " . $bot->getName());
        $this->bots[] = $bot;
        return true;
    }

    /**
     * Remove an existing bot. Check if the bot exists by proofing its type and ID.
     * 
     * @param Object $bot    The bot to remove
     * @return boolean       Return true if the bot was found and removed successfully, otherwise false.
     */
    public function removeBot($bot) {
        foreach($this->bots as $k => $v) {
            if ((strcmp($v->getType(), $bot->getType()) === 0) && ($v->getID() == $bot->getID())) {
                Log::debug(self::$TAG, "bot was removed: " . $bot->getName());
                unset($this->bots[$k]);
                return true;
            }
        }
        Log::warning(self::$TAG, "cannot remove bot, it does not exist: " . $bot->getName());
        return false;
    }

    /**
     * Find a bot given its type and ID.
     * 
     * @param string $botType    Bot type
     * @param string $botId      Bot ID
     * @return                   The bot if found in manager's current bots, otherwise null.
     */
    public function findBot($botType, $botId) {
        foreach($this->bots as $bot) {
            if ((strcmp($botType, $bot->getType()) === 0) && ($bot->getID() == $botId)) {
                return $bot;
            }
        }
        return null;
    }

    /**
     * Given a bot type try to find its class in registered classes.
     * 
     * @param string $botType       Bot type
     * @return sting                Return null if the class could not be found.
     */
    public function findBotClass($botType) {
        if (is_null($botType) || (strlen($botType) === 0)) {
            return null;
        }
        $foundclass = null;
        foreach($this->botClasses as $botclass) {
            if (strpos($botclass, $botType) !== false) {
                $foundclass = $botclass;
                break;
            }
        }
        return $foundclass;
    }

    /**
     * Periodically call this update method.
     */
    public function update() {
        foreach($this->bots as $bot) {
            $bot->update();
        }
    }

    /**
     * Load all bots of registered bot classes from database.
     */
    public function loadBots() {
        foreach($this->botClasses as $botclass) {
            $ids = $botclass::getAllIDs();
            if (is_null($ids)) {
                Log::warning(self::$TAG, "no database table found for bot class: " . $botclass);
                continue;
            }
            $loadresult = false;
            foreach($ids as $id) {
                $newbot = $this->createBot($botclass, $id, $loadresult);
                if (is_null($newbot)) {
                    Log::warning(self::$TAG, " could not create bot");
                    return false;
                }
                $this->addBot($newbot);
            }
        }
    }

    /**
     * Create and load a bot given its class and ID.
     * 
     * @param string $botClass      The bot class. It must be a full qualified name of a bot class ready for instantiation.
     * @param int $botId            Bot ID used for loading its data.
     * @param boolean $loadResult   Result of loading.
     * @return Object               The new bot, or null if it could not be created
     */
    public function createBot($botClass, $botId, &$loadResult) {

        try {
            $bot = $botClass::create($this->ts3Server);
        }
        catch (Exception $e) {
            Log::warning(self::$TAG, "could not create instance of bot class: " . $botClass);
            Log::warning(self::$TAG, "  reason: " . $e->getMessage());

            $loadResult = false;
            return null;
        }
        $loadResult = $bot->initialize($botId);
        return $bot;
    }

    /**
     * Call this method whenever a server event was received.
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     */
    public function notifyServerEvent($event, $host) {

        $TYPE_CHANNEL = "channel";
        $TYPE_CLIENT  = "client";

        $type = $event->getType();

        //Log::debug(self::$TAG, "ts3 server event received, type: " . $type . ", host: " . $host);

        // check if the event was something about channels
        if (strlen($type) >= strlen($TYPE_CHANNEL) &&
            strcmp(substr($type, 0, strlen($TYPE_CHANNEL)), $TYPE_CHANNEL) === 0) {

            //Log::debug(self::$TAG, " updating the channel list");
            $this->ts3Server->channelListReset();
        }
        if (strlen($type) >= strlen($TYPE_CLIENT) &&
            strcmp(substr($type, 0, strlen($TYPE_CLIENT)), $TYPE_CLIENT) === 0) {

            //Log::debug(self::$TAG, " updating the client list");
            $this->ts3Server->clientListReset();
        }

        // notify now all bots
        foreach($this->bots as $bot) {
            $bot->onServerEvent($event, $host);
        }
    }

    /**
     * Notify about an update of bot configuration. Usually this means that the bot
     * config was changed in the database, the bot should load it and reflect
     * the changes, if it exists.
     * 
     * @param string $botType   The bot type
     * @param int $botId        The bot ID
     * @return boolean          Return true if the bot was found and updated successfully, otherwise false.
     */
    public function notifyBotUpdate($botType, $botId) {
        Log::verbose(self::$TAG, "notify bot update: " . $botType . ", " . $botId);
        $bot = $this->findBot($botType, $botId);
        if ($bot) {
            $bot->onConfigUpdate();
            return true;
        }
        return false;
    }

    /**
     * Notify about a bot creation in database. The bot will be loaded and added to the bot manager.
     * 
     * @param string $botType   The bot type
     * @param int $botId        The bot ID
     * @return boolean          Return true if the bot was added successfully, otherwise false.
     */
    public function notifyBotAdd($botType, $botId) {
        Log::verbose(self::$TAG, "notify bot add: " . $botType . ", " . $botId);
        if ($this->findBot($botType, $botId)) {
            Log::warning(self::$TAG, " could not add bot, it already exists!");
            return false;
        }

        $botclass = $this->findBotClass($botType);
        if (is_null($botclass)) {
            Log::warning(self::$TAG, " could not find bot class!");
            return false;
        }

        $loadResult = false;
        $bot = $this->createBot($botclass, $botId, $loadResult);
        if (is_null($bot)) {
            Log::warning(self::$TAG, " could not create and add bot");
            return false;
        }
        $this->addBot($bot);

        return true;
    }

    /**
     * Notify about a bot deletion. The bot will be removed from the bot manager.
     * 
     * @param string $botType   The bot type
     * @param int $botId        The bot ID
     * @return boolean          Return true if the bot was found and deleted successfully, otherwise false.
     */
    public function notifyBotDelete($botType, $botId) {
        Log::verbose(self::$TAG, "notify bot delete: " . $botType . ", " . $botId);
        $bot = $this->findBot($botType, $botId);
        if ($bot) {
            Log::verbose(self::$TAG, "deleting bot");
            $this->removeBot($bot);
            return true;
        }
        return false;
    }
}