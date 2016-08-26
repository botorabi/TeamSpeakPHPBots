<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\bots\chatbot;
use com\examples\bots\chatbot\ChatBotModel;
use com\tsphpbots\bots\BotBase;
use com\tsphpbots\utils\Log;


/**
 * Class for chat bot
 * 
 * @created:  4nd August 2016
 * @author:   Botorabi (boto)
 */
class ChatBot extends BotBase {

    /**
     * @var string  This tag is used for logs
     */
    protected static $TAG = "ChatBot";

    /**
     *
     * @var ChatBotModel  Database model of the bot. All bot paramerers are hold here.
     */
    protected $model = null;

    /**
     * @var array A queue used for replying to messages
     */
    protected $replyQueue = [];

    /**
     * @var boolean  Flag for issuring an initial greet.
     */
    protected $initialGreet = false;

    /**
     * @var array A queue for fresh entered users in bot's channel
     */
    protected $enterChannelQueue = [];
    
    /**
     * Construct a chat bot.
     */
    public function __construct() {
        BotBase::__construct();
        $this->model = new ChatBotModel;
    }

    /**
     * This is a creation policy. This bot needs an own connection.
     * 
     * @implements base class method
     * 
     * @param string $nickName  The nick name for the connection.
     * @return boolean          Return true if an own server connection should be created for the bot.
     */
    public function needsOwnServerConnection(&$nickName) {
        // replace any blanks in the name
        $nickName = $this->model->nickName;
        return true;
    }

    /**
     * Get all available bot IDs.
     * This is used by bot manager for loading all available bots from database.
     * 
     * @implements base class method
     * 
     * @return array    Array of all available bot IDs, or null if there is no corresponding table in database.
     */
    static public function getAllIDs() {
        return (new ChatBotModel)->getAllObjectIDs();
    }

    /**
     * Create a new bot instance.
     * 
     * @implements base class method
     * 
     * @return              New instance of the bot.
     */
    public static function create() {
        return new ChatBot();
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
        $this->model->greetingText = trim($this->model->greetingText);
        $this->model->nickName = trim($this->model->nickName);

        if (strlen(trim($this->model->nickName)) === 0) {
            Log::warning(self::$TAG, "empty nick name detected, deactivating the bot!");
            $this->model->active = 0;
        }
        else {
            Log::debug(self::$TAG, " bot succesfully loaded, name: '" . $this->getName() . "'");
        }

        // check if there is a geeting text
        if (strlen($this->model->greetingText) > 0) {
            $this->initialGreet = true;
        }

        try {
            $me = $this->ts3Server->whoamiGet("client_id");
            $this->ts3Server->clientMove($me, $this->model->channelID);
            Log::debug(self::$TAG, "bot moved successfully to channel: " . $this->model->channelID);
        }
        catch(\TeamSpeak3_Adapter_ServerQuery_Exception $e) {
            Log::warning(self::$TAG, "cannot move to channel: " . $this->model->channelID);
            Log::warning(self::$TAG, "  reason: " . $e->getMessage());
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
     * @return ChatBotModel  The database model of this bot.
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

        // skip event handling if the bot is not active
        if ($this->model->active == 0) {
            return;
        }
       
        //Log::verbose(self::$TAG, "bot '" . $this->model->name . "' got event: " . $event->getType());

        if (strcmp($event->getType(), "cliententerview") === 0) {
            $data = $event->getData();
            $clientid = $data["clid"];

            // did the client directly entered bot's channel?
            if ($this->isInChannel($clientid, $this->model->channelID)) {
                $cnick = $data["client_nickname"];
                $greet = "Hello " . $cnick . "!";
                // enqueue a greeting for next update step
                $this->replyQueue[] = ["targetId" => 0, "msg" => $greet];
            }
            return;
        }
        else if (strcmp($event->getType(), "clientmoved") === 0) {
            $data      = $event->getData();
            $clientid  = (int)$data["clid"];
            $channelid = (int)$data["ctid"];
            // check if a new client was moved to bot's channel
            if((strlen($this->model->greetingText) > 0) &&
               ($this->model->channelID == $channelid) &&
                $this->isInChannel($clientid, $this->model->channelID)) {

                // unfortunately, the t3 server query sends this event twice, we have to deal with it
                $this->enterChannelQueue[$clientid] = ["targetId" => 0, "msg" => $this->model->greetingText];
                //Log::verbose(self::$TAG, "client entered my channel: " . $clientid);
            }
            return;
        }
        else if (strcmp($event->getType(), "textmessage") === 0) {
            $data    = $event->getData();
            $target  = 0;
            $pardner = null;
            $source  = (int)$data["invokerid"];
            $me      = (int)$host->whoami()["client_id"];

            if (isset($data["target"])) {
                $target = (int)$data["target"];
            }
            if (isset($data["invokername"])) {
                $pardner = $data["invokername"];
            }

            //Log::verbose(self::$TAG, "me: " . $me . ", source: " . $source . ", target: " . $target);

            // consider echos!
            if(($source !== $target) && ($source !== $me)) {
                $text = $data["msg"];
                $reply = $this->replyMessage($text);
                if (!is_null($reply)) {
                    // enqueue the reply for next update step
                    $this->replyQueue[] = ["targetId" => 0, "msg" => $reply, "pardner" => $pardner];
                }
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

        if ($this->initialGreet === true) {
            $this->initialGreet = false;
            $text = str_replace("<nick>", "", $this->model->greetingText);
            $this->sendMessage($this->model->channelID, null, $text);
        }

        // this avoids 'clientmoved' event duplication
        foreach($this->enterChannelQueue as $q) {
            $this->replyQueue[] = $q;
        }
        $this->enterChannelQueue = [];

        foreach($this->replyQueue as $q) {
            $targetId = $q["targetId"] == 0 ? null : [$q["targetId"]];
            $reply    = $q["msg"];
            $pardner  = empty($q["pardner"]) ? null : $q["pardner"];
            $this->sendMessage($this->model->channelID, $targetId, $reply, $pardner);
        }
        $this->replyQueue = [];
    }

    /**
     * Check if a client is in channel with given ID.
     * 
     * @param int $clientID     Client ID
     * @param int $channelID    Channel ID
     * @return boolean          True if the client is in given channel, otherwise false.
     */
    protected function isInChannel($clientID, $channelID) {
        if (!$channelID) {
            return false;
        }
        try {
            $channel = $this->ts3Server->channelGetById($channelID);
            $clients = $channel->clientList();
            foreach($clients as $client) {
                if ($client["clid"] == $clientID) {
                    return true;
                }
            }
        }
        catch(\Exception $e) {
            Log::warning(self::$TAG, "could not check channel, reason: " . $e->getMessage());
        }
        return false;
    }

    /**
     * Send a message to clients in a channel.
     * 
     * @param int $channelID        Channel ID
     * @param mixed $clientIDs      Client ID list of recipients in channel, or null for all clients in channel.
     * @param string $msg           Message to send
     * @param string $chatPardner   The chat partner the message is meant for. Pass null if no partner is addressed.
     */
    protected function sendMessage($channelID, $clientIDs, $msg, $chatPardner = null) {

        try {
            $channel = $this->ts3Server->channelGetById($channelID);
        }
        catch(\Exception $e) {
            Log::warning(self::$TAG, "could not send message, reason: " . $e->getMessage());
            return;
        }
        $text = "[" . $this->model->nickName . (!is_null($chatPardner) ? " -> " . $chatPardner : "") . "]: " . $msg;
        if (is_null($clientIDs)) {
            $channel->message($text);
        }
        else {
            $clients = $channel->clientList();
            foreach($clients as $client) {
                if (in_array($client["clid"], $clientIDs)) {
                    $client->message($text);
                }
            }
        }
    }

    /**
     * Check if the given haystack string contains the needle string.
     * 
     * @param string $haystack  The source string
     * @param string $needle    The string to search for
     * @return boolean          True if the haystack string contrins the given needle, otherwise false.
     */
    protected function strContains($haystack, $needle) {
        $h = strtolower($haystack);
        $n = strtolower($needle);
        return (strpos($h, $n) !== false);
    }

    /**
     * Reply to incoming message.
     * 
     * @param string $msg       Incoming message
     * @return string           Reply text, or null if there is no reply to given message.
     */
    protected function replyMessage($msg) {
        // limit the text length
        $STR_MAX_LEN = 256;
        $text = strtolower((strlen($msg) > $STR_MAX_LEN) ? substr($msg, 0, $STR_MAX_LEN) : $msg);
        
        $reply = null;
        if ($this->strContains($text, "help")) {
            $reply = "I understand also following commands: date, weather <city>";
        }
        else if ($this->strContains($text, "hi") || $this->strContains($text, "hello") || $this->strContains($text, "hey")) {
            $reply = "Hi my friend. I am a chat bot, tell me something and I try to sound smart.";
        }
        else if ($this->strContains($text, "how") &&
                 $this->strContains($text, "are") &&
                 $this->strContains($text, "you")) {
            $reply = "I am well, thank you. How are you?";
        }
        else if ($this->strContains($text, "who") &&
                 $this->strContains($text, "your") &&
                 ($this->strContains($text, "father") || $this->strContains($text, "god"))) {
            $reply = "It's Botorabi, see github.com/botorabi";
        }
        else if ($this->strContains($text, "date")) {
            $reply = "It is: " . date('l jS \of F Y, H : i : s');
        }
        else if ($this->strContains($text, "weather")) {
            $parts = explode(" ", $msg);
            $city = "berlin";
            if (count($parts) > 1) {
                $city = $parts[1];
            }
            $reply = "Weather: " . $this->getWeather($city);
        }

        return $reply;
    }

    /**
     * Get the current weather information given a city as location.
     * 
     * @param string $city              City
     * @param boolean $useMetricUnits   Pass true in order to use metric units, otherwise user US units.
     * @return string                   Return a text containing weather information
     */
    protected function getWeather($city, $useMetricUnits = true) {

        $baseurl = "https://query.yahooapis.com/v1/public/yql?q=select%20*%20from%20weather.forecast%20where%20woeid%20in%20(select%20woeid%20from%20geo.places(1)%20where%20text%3D%22@CITY@%22)@UNIT@&format=json&env=store%3A%2F%2Fdatatables.org%2Falltableswithkeys";

        $c = trim(str_replace(" ", "\20", $city));
        $url = str_replace("@CITY@", $c, $baseurl);
        if ($useMetricUnits) {
            $url = str_replace("@UNIT@", "%20and%20u%3D'c'", $url);
        }
        else {
            $url = str_replace("@UNIT@", "", $url);
        }

        $content = @file_get_contents($url);
        if ($content === false) {
            return null;
        }
        $response = json_decode($content, true);
        if (is_null($response)) {
            return null;
        }
        //echo print_r($response, true);
        try {
            $ch = &$response["query"]["results"]["channel"];
            $units = $ch["units"];

            $location = $ch["location"]["city"] . " (" . $ch["location"]["country"] . ")";
            $temperatur = $ch["item"]["condition"]["temp"] . " " . $units["temperature"] . ", " . $ch["item"]["condition"]["text"];
            $windspeed = $ch["wind"]["speed"] . " ". $units["speed"];
            $winddir = $ch["wind"]["direction"];
            $humidity = $ch["atmosphere"]["humidity"];
            $pressure = $ch["atmosphere"]["pressure"] . " " . $units["pressure"];
        }
        catch (Exception $ex) {
            return null;
        }
        
        $text = "Powered by Yahoo! Weather, ";
        $text .= $location . ", ";
        $text .= "Temperatur: " . $temperatur . ", ";
        $text .= "Wind: speed " . $windspeed . " direction " . $winddir . ", ";
        $text .= "Pressure: " . $pressure . ", ";
        $text .= "Humidity: " . $humidity;

        return $text;
    }
}
