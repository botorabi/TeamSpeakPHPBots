<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
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
     * @var sting Bot type
     */
    public static $BOT_TYPE = "GreetingBot";

    /**
     *
     * @var GreetingBotModel  Database model of the bot. All bot paramerers are hold here.
     */
    protected $model = null;

    /**
     * Construct a greeting bot.
     * 
     * @param  $server      TS3 server object
     * @throws Exception    Throws exception if the given server is invalid.
     */
    public function __construct($server) {
        BotBase::__construct($server);
        $this->model = new GreetingBotModel;
    }

    /**
     * Create a new bot instance.
     * 
     * @implements base class method
     * 
     * @param $server       TS3 Server object
     * @return              New instance of the bot.
     */
    public static function create($server) {
        return new GreetingBot($server);
    }

    /**
     * Get the bot type.
     * 
     * @implements base class method
     * 
     * @return string       The bot type
     */
    public function getType() {
        return self::$BOT_TYPE;
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
     * Load the bot from database.
     * 
     * @implements base class method
     * 
     * @param int $id       Bot ID (database table row ID)
     * @return boolean      Return false if the object was not loaded, otherwise true.
     */
    public function load($id) {

        Log::debug(self::$TAG, "loading bot type: " . $this->getType() . ", id " . $id);

        if ($this->model->loadObject($id) === false) {
            Log::warning(self::$TAG, "could not load bot from database: id " . $id);
            return false;
        }

        if (strlen(trim($this->model->greetingText)) === 0) {
            Log::warning(self::$TAG, "empty greeting text detected, deactivating the bot!");
            $this->model->active = false;
        }
        else {
            Log::debug(self::$TAG, " bot succesfully loaded, name: '" . $this->getName() . "'");
        }

        return true;
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

            $this->load($this->getID());
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

        if (strcmp($event->getType(), "cliententerview") === 0) {

            $data = $event->getData();
            $clid = $data["clid"];
            $clnick = $data["client_nickname"];

            Log::verbose(self::$TAG, "going to greet a new client: " . $clnick);

            try {
                // get the client
                $client = $this->ts3Server->clientGetById($clid);
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
    public function update() {

        // skip updating if the bot is not active
        if ($this->model->active == 0) {
            return;
        }

        //Log::verbose(self::$TAG, "bot " . $this->getName() . " was updated");
    }
}
