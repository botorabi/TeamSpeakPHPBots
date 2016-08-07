<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

require_once("../../libraries/TeamSpeak3/TeamSpeak3.php");
require_once("../config/Configuration.php");

use com\tsphpbots\service\ServerQuery;
use com\tsphpbots\bots\BotManager;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;


/**
 * Main entry of GreetingBot example's bot service
 * 
 * @created:  2nd August 2016
 * @author:   Botorabi
 */
class App {

    /**
     * @var string Log tag
     */
    protected static $TAG = "App";

    /**
     *
     * @var string App version
     */
    protected $appVersion = "";

    /**
     *
     * @var array Search paths for finding php files
     */
    protected $SEARCH_PATHS = [];

    /**
     *
     * @var App Static ref to app used for TS3 framework callback functions.
     */
    protected static $theApp = null;
    
    protected $ts3Server   = null;
    protected $botManager  = null;
    protected $botSrvQuery = null;

    /**
     * Construct the application, it initializes all necessary resources.
     */
    public function __construct() {
        self::$theApp = $this;
        $this->init();
    }

    /**
     * Initialize the application
     */
    protected function init() {

        // start a session
        session_start();

        $this->appVersion = Configuration::$TSPHPBOT_CONFIG_BOT_SERVICE["version"];

        //! Main source directory used in autoloader for finding php files
        $this->SEARCH_PATHS[] = Configuration::$TSPHPBOT_CONFIG_BOT_SERVICE["appSrc"];
        //! The path of TS3PHPBots library
        $this->SEARCH_PATHS[] = Configuration::$TSPHPBOT_CONFIG_BOT_SERVICE["libSrc"];

        //! Setup our class file loader
        spl_autoload_register(function ($name) {
            //echo "**** loading: " . $name ."\n";
            // exclude the ts3 lib namespace, it has an own loader
            $pos = strpos($name, "TeamSpeak3");
            if ($pos === false) {
                $file = $this->findPath($name);
                if (!is_null($file)) {
                    include $file;
                }
                else {
                    Log::error("AUTOLOAD", "Module does not exist: " . $name);
                    /*
                    Log::error("AUTOLOAD", "Backtrace:");
                    foreach(debug_backtrace() as $depth => $trace) {
                        Log::raw("  [" . $depth . "]: " . $trace["file"] . ", line: " . $trace["line"] . ", function: " . $trace["function"]);
                    }
                    */
                    throw new Exception("Could not find the requested file: " . $name . ".php");
                }
            }
        });
    }

    /**
     * Try to find the given module in search paths. Return null if it could not be found.
     * 
     * @param string $moduleName  Module name
     * @return string   Full path of php file, or null if it could not be found.
     */
    protected function findPath($moduleName) {
        foreach($this->SEARCH_PATHS as $path) {
            $fullpath = $path . "/" . $moduleName;
            $fullpath = str_replace("\\", "/", $fullpath);
            if (file_exists($fullpath . ".php")) {
                return $fullpath . ".php";
            }
        }
        return null;
    }

    /**
     * Start the application
     */
    public function start() {

        // use echo for logs
        Log::useErrorLog(false);
        
        Log::raw("********************************************");
        Log::raw("* Greeting Bot Service");
        Log::raw("* Copyright 2016");
        Log::raw("* All Rights Reserved by TeamSpeakPHPBots");
        Log::raw("* https://github.com/botorabi/TeamSpeakPHPBots");
        Log::raw("*");
        Log::raw("* Version: " . $this->appVersion);
        Log::raw("* Author: Botorabi (boto)");
        Log::raw("*");
        Log::raw("* Date: " . date('D, d M Y H:i:s'));
        Log::raw("********************************************");
        Log::raw("");

        Log::info(self::$TAG, "starting the bot service");

        $terminate = false;
        while(!$terminate) {
            try {
                Log::info(self::$TAG, "starting bots");
                $terminate = $this->mainLoop();
            }
            catch(Exception $e) {
                Log::error(self::$TAG, "*** An exception occurred. Reason: " . $e->getMessage());
                Log::error(self::$TAG, "***  Try to recover in a few seconds...");
                sleep(5);
            }
        }
    }

    /**
     * Connect TS3 server.
     * @return TS3 Server 
     */
    protected function connectTS3Server() {

        $nickname = str_replace(" ", "%20", Config::getTS3ServerQuery("nickName"));
        // connect to local server, authenticate and spawn an object for the virtual server on port 9987
        $querytext = "serverquery://".  Config::getTS3ServerQuery("userName") . ":" .
                                        Config::getTS3ServerQuery("password") . "@" . 
                                        Config::getTS3ServerQuery("host") . ":" .
                                        Config::getTS3ServerQuery("hostPort") . "/?server_port=" .
                                        Config::getTS3ServerQuery("vServerPort") .
                                        "&blocking=0&timeout=" . Config::getTS3ServerQuery("pollInterval") .
                                        "&nickname=" . $nickname;   
        $server = TeamSpeak3::factory($querytext);
        return $server;
    }

    //! Start the main loop for monitoring.
    protected function mainLoop() {
        // initialize
        TeamSpeak3::init();

        try
        {
            /* subscribe to various events */
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryConnected", "App::onConnect");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryCommandStarted", "App::onCommand");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("serverqueryWaitTimeout", "App::onTimeout");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyLogin", "App::onLogin");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyEvent", "App::onEvent");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyTextmessage", "App::onTextmessage");
            TeamSpeak3_Helper_Signal::getInstance()->subscribe("notifyServerselected", "App::onSelect");

            /* connect to server, login and get TeamSpeak3_Node_Host object by URI */
            Log::debug(self::$TAG, "connecting the TS3 server...");
            $vserver = $this->connectTS3Server();
            $this->ts3Server = $vserver;

            /* register for interested events */

            /* NOTE: If you register for both, "server" and "channel", then take into account that you might get
             * alot of notifications twice! We better take one of them.
             */
            $vserver->notifyRegister("channel");
            //$vserver->notifyRegister("server");
            //$vserver->notifyRegister("textserver");
            //$vserver->notifyRegister("textchannel");
            //$vserver->notifyRegister("textprivate");
        }
        catch(Exception $e)
        {
            Log::error(self::$TAG, "error occurred during initializing the ts3 server query interface, reason: " . $e->getMessage());
            return true;
        }

        Log::debug(self::$TAG, "setup the bot manager");
        $this->botManager = new BotManager($vserver);
        
        // register our GreetingBot, perfere to use forward slashes in order to avoid specifying special chars by accident
        $this->botManager->registerBotClass("com/examples/bots/greetingbot/GreetingBot");
        
        // load and setup bots from database
        $this->botManager->loadBots();

        Log::debug(self::$TAG, "setup the service query");
        
        // this allows communication between the bot and web service (e.g. query server status or notify about bot update in database)
        $this->botSrvQuery = new ServerQuery;
        $this->botSrvQuery->initialize($this->botManager);

        // app's main loop
        while(!$this->botSrvQuery->terminate()) {
            try {
                // wait for events
                //Log::verbose(self::$TAG, "wait for events");
                $vserver->getAdapter()->wait();
            }
            catch(Exception $e) {
                // an exception during shutdown is not currently not useful, ignore it!
                if (!$this->botSrvQuery->terminate()) {
                    Log::warning(self::$TAG, "an exception occured: " . $e->getMessage());
                    Log::warning(self::$TAG, "  backtrance: " . $e->getTraceAsString());
                }
            }
        }

        Log::info(self::$TAG, "shutting down the service");
        $this->botSrvQuery->shutdown();
        return true;
    }

    /**
     * Update the application. Call this periodically.
     */
    protected function update() {
        $this->botManager->update();
        $this->botSrvQuery->update();

        if ($this->botSrvQuery->terminate()) {
            try {
                $this->ts3Server->request("quit");
            }
            catch(Exception $e) {}
        }
    }

    // ================= [ BEGIN OF TS3Teamspeak CALLBACK FUNCTION DEFINITIONS ] =================

    /**
     * Callback method for 'serverqueryConnected' signals.
     *
     * @param  TeamSpeak3_Adapter_ServerQuery $adapter
     * @return void
     */
    public static function onConnect(TeamSpeak3_Adapter_ServerQuery $adapter) {
        Log::info(self::$TAG, "connected to TeamSpeak 3 Server on " . $adapter->getHost());
        $version = $adapter->getHost()->version();
        Log::info(self::$TAG, "  server is running with version " . $version["version"] . " on " . $version["platform"]);
    }

    /**
     * Callback method for 'serverqueryCommandStarted' signals.
     *
     * @param  string $cmd
     * @return void
     */
    public static function onCommand($cmd) {
        // the login command unveils the user name / password, avoid this!
        /*
        if (strcmp("login", substr($cmd, 0, 5)) === 0) {
            $cmd = "login...";
        }
        Log::verbose(self::$TAG, "starting command " . $cmd);
        */
    }

    /**
     * Callback method for 'serverqueryWaitTimeout' signals.
     *
     * @param  integer $seconds
     * @return void
     */
    public static function onTimeout($seconds, TeamSpeak3_Adapter_ServerQuery $adapter) {
        if($adapter->getQueryLastTimestamp() < time()-300) {
          Log::debug(self::$TAG, "sending keep-alive command");
          $adapter->request("clientupdate");
        }
        self::$theApp->update();
    }

    /**
     * Callback method for 'notifyLogin' signals.
     *
     * @param  TeamSpeak3_Node_Host $host
     * @return void
     */
    public static function onLogin(TeamSpeak3_Node_Host $host) {
        Log::debug(self::$TAG, "authenticated as user " . $host->whoamiGet("client_login_name"));
    }

    /**
     * Callback method for 'notifyEvent' signals.
     *
     * @param  TeamSpeak3_Adapter_ServerQuery_Event $event
     * @param  TeamSpeak3_Node_Host $host
     * @return void
     */
    public static function onEvent(TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host) {
        self::$theApp->botManager->notifyServerEvent($event, $host);
    }

    /**
     * Callback method for 'notifyTextmessage' signals.
     *
     * @param  TeamSpeak3_Adapter_ServerQuery_Event $event
     * @param  TeamSpeak3_Node_Host $host
     * @return void
     */
    public static function onTextmessage(TeamSpeak3_Adapter_ServerQuery_Event $event, TeamSpeak3_Node_Host $host) {
        //Log::debug(self::$TAG, "client " . $event["invokername"] . " sent textmessage: " . $event["msg"]);
    }

    /**
     * Callback method for 'notifyServerselected' signals.
     *
     * @param  string $host
     * @return void
     */
    public static function onSelect(TeamSpeak3_Node_Host $host) {
        Log::verbose(self::$TAG, "selected virtual server with ID " . $host->serverSelectedId());
    }
}

// let's go
$app = new App();
$app->start();
