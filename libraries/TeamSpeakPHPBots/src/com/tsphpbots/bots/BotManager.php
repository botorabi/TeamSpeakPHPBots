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
use com\tsphpbots\config\Config;
use com\tsphpbots\teamspeak\TSServerConnections;


/**
 * This class manages the creation and the lifecycle of bots.
 * 
 * @package   com\tsphpbots\bots
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
     * @var Object  TS3 server connection manager
     */
    protected $ts3Connection = null;

    /**
     * @var Object  TS3 server connections, table consisting of stream/serverObject pairs.
     */
    protected $ts3ServerConnections = [];

    /**
     * @var Object TS3 default server connection
     */
    protected $ts3DefaultConnection = null;

    /**
     * @var int  Used for creating unique nicknames for new connections
     */
    protected $numConnections = 0;

    /**
     * @var array All nicknames already used for server connections.
     */
    protected $usedNickNames = [];

    /**
     * Construct the bot manager.
     */
    public function __construct() {
        $this->ts3Connection = new TSServerConnections();
    }

    /**
     * Initialize the bot manager.
     * 
     * @return boolean      Return true if bot manager was successfully initialized, otherwise false.
     */
    public function initialize() {
        // create a default server connection
        $this->ts3DefaultConnection = $this->createServerConnection(Config::getTS3ServerQuery("nickName"));
        if (is_null($this->ts3DefaultConnection)) {
            Log::error(self::$TAG, "could not initialize bot manager, no teamspeak server connection!");
            return false;
        }
        return true;
    }

    /**
     * Update the application.
     * 
     * @param $stream  The server connection stream
     */
    public function update() {
        foreach($this->bots as $bot) {
            //Log::debug(self::$TAG, "update bot: " . $stream . ", bot type: " . $bot->getType());
            $bot->update();
        }
        // calculate the timeout for polling the server connections
        if ($this->numConnections === 0) {
            $timeout = 1000;
        }
        else {
            $timeout = (Config::getTS3ServerQuery("pollInterval") * 1000.0) / $this->numConnections;
        }
        // limit the timout to a minimum of 100 ms
        $timeout = $timeout < 100 ? 100 : $timeout;       
        $this->ts3Connection->update($timeout);
    }

    /**
     * Shutdown the bot manager.
     */
    public function shutdown() {
        Log::debug(self::$TAG, "shutting down the bot manager");

        foreach($this->bots as $bot) {
            $bot->onShutdown();
        }

        $this->bots = [];
        $this->ts3Connection->shutdown();
        $this->ts3Connection = null;
        $this->ts3ServerConnections = [];
        $this->ts3DefaultConnection = null;
        $this->numConnections = 0;
        $this->usedNickNames = [];
    }

    /**
     * Register a new bot class. This is usually called during application initialization for
     * bots which wish to have the automatic database and house-keeping functinoality
     * of the BotManager. 
     * 
     * The bot type is expected to be the last element in a full qualified class path including the package namespace.
     * For example following bot class 'com/myapp/bots/MyBot' is expected to have the bot type 'MyBot' which is defined
     * in the bot class MyBot, see BotBase class' method getType().
     * 
     * @param string $botClass      The full qualified name (including package path) of the bot class to register.
     *                              For package name separation one can also use forward slashes.
     * @return boolean              Return false if the bot class is already registered, otherwise true.
     */
    public function registerBotClass($botClass) {
        $cleanpath = str_replace("/", "\\", $botClass);
        foreach($this->botClasses as $class) {
            if (strcmp($class, $cleanpath) === 0) {
                return false;
            }
        }
        $this->botClasses[] = $cleanpath;
        return true;
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
                    continue;
                }
                $this->addBot($newbot);
            }
        }
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
     * We expect that the bot type is the last element in the class path.
     * 
     * @param string $botType       Bot type
     * @return string               Return null if the class could not be found.
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
     * Create and load a bot given its class and ID.
     * 
     * @param string $botClass      The bot class. It must be a full qualified name of a bot class ready for instantiation.
     * @param int $botId            Bot ID used for loading its data.
     * @param boolean $loadResult   Result of loading.
     * @return Object               The new bot, or null if it could not be created
     */
    public function createBot($botClass, $botId, &$loadResult) {
        try {
            $bot = $botClass::create();
            $bot->loadData($botId);
            Log::debug(self::$TAG, "creating bot '" . $bot->getName() . "' of type " . $bot->getType());
            // check if the bot needs an own ts3 server connection
            $nickname = Config::getTS3ServerQuery("nickName");
            if ($bot->needsOwnServerConnection($nickname)) {
                Log::debug(self::$TAG, "  bot needs an own ts3 server connection, using nickname '" . $nickname . "'");
                $nickname = $this->createUniqueNickName($nickname);
                $srv = $this->createServerConnection(trim(str_replace(" ", "%20", $nickname))); // no blanks are allowed in nick name
            }
            else {
                $srv = $this->ts3DefaultConnection;
            }
            $bot->setServer($srv);
            $loadResult = $bot->initialize();
        }
        catch (Exception $e) {
            Log::warning(self::$TAG, "could not create instance of bot class: " . $botClass);
            Log::warning(self::$TAG, "  reason: " . $e->getMessage());

            $loadResult = false;
            return null;
        }
        return $bot;
    }

    /**
     * Create a TS3 server connection with given nickname.
     * 
     * @param string $nickName  Nick name used for the connection
     * @return Object           TS3 server object, or null if something went wrong
     */
    protected function createServerConnection($nickName) {
        $server = $this->ts3Connection->createConnection($nickName, array($this, "onNotifyServerEvent"));
        if (!is_null($server)) {
            $stream = $server->getParent()->getParent()->getTransport()->getStream();
            $this->ts3ServerConnections[] = ["stream" => $stream, "server" => $server];
            $this->numConnections++;
        }
        Log::debug(self::$TAG, "total count of server connections: " . $this->numConnections);
        return $server;
    }

    /**
     * Create a unique connection nick name given a wished name. If the name is
     * already in use then a postfix will be appended.
     * 
     * @param string $nickName  The wished nick name
     * @return string           Return a unique nick name
     */
    protected function createUniqueNickName($nickName) {
        $postfix = 0;
        while(isset($this->usedNickNames[$nickName])) {
            $nickName .= $postfix++;
        }
        $this->usedNickNames[$nickName] = $nickName;
        return $nickName;
    }

    /**
     * Given a connection stream return the corresponding server.
     * 
     * @param Object $stream    Connection stream
     * @return array            The TS3 server object, null if no entry exists with that stream.
     */
    protected function getServerByStream($stream) {
        foreach($this->ts3ServerConnections as $srv) {
            if ($srv["stream"] === $stream) {
                return $srv["server"];
            }
        }
        return null;
    }

    /**
     * Call this method whenever a server event was received. This is called by connection manager.
     * 
     * @param Object $event        Event received from ts3 server
     * @param Object $host         Server host
     * @param Object $stream       The server connection stream.
     */
    public function onNotifyServerEvent($event, $host, $stream) {

        $TYPE_CHANNEL = "channel";
        $TYPE_CLIENT  = "client";

        $type = $event->getType();

        $ts3server = $this->getServerByStream($stream);
        if (is_null($ts3server)) {
            Log::debug(self::$TAG, "ignoring event of an unknown stream: " . $stream);
            return;
        }

        //Log::debug(self::$TAG, "ts3 server event received, type: " . $type . ", host: " . $host);

        // check if the event was something about channels
        if (strlen($type) >= strlen($TYPE_CHANNEL) &&
            strcmp(substr($type, 0, strlen($TYPE_CHANNEL)), $TYPE_CHANNEL) === 0) {

            //Log::debug(self::$TAG, " updating the channel list");
            $ts3server->channelListReset();
        }
        if (strlen($type) >= strlen($TYPE_CLIENT) &&
            strcmp(substr($type, 0, strlen($TYPE_CLIENT)), $TYPE_CLIENT) === 0) {

            //Log::debug(self::$TAG, " updating the client list");
            $ts3server->clientListReset();
        }

        // notify now all bots
        foreach($this->bots as $bot) {
            if ($bot->getServerStream() === $stream) {
                $bot->onServerEvent($event, $host);
                break;
            }
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

    /**
     * Notify about a message for the bot.
     * 
     * @param string $botType   The bot type
     * @param int $botId        The bot ID
     * @param string $text      Message text
     * @return boolean          Return true if the bot was found and the message delivered successfully, otherwise false.
     */
    public function notifyBotMessage($botType, $botId, $text) {
        Log::verbose(self::$TAG, "notify bot about a message: " . $botType . ", " . $botId);
        $bot = $this->findBot($botType, $botId);
        if ($bot) {
            $bot->onReceivedMessage($text);
            return true;
        }
        return false;
    }

}