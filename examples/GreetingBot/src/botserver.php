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
use com\tsphpbots\utils\Log;


/**
 * Main entry of MultipleBotType example's bot service
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
     * Construct the application, it initializes all necessary resources.
     */
    public function __construct() {
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

        Log::info(self::$TAG, "setup the bot manager");
        $botmanager = new BotManager();
        if (!$botmanager->initialize()) {
            Log::error(self::$TAG, "could not initialize the bot manager!");
            return;
        }

        // this allows communication between the bot and web service (e.g. query server status or notify about bot update in database)
        $botsrvquery = new ServerQuery;
        if ($botsrvquery->initialize($botmanager) === false) {
            Log::error(self::$TAG, "could not initialize bot server query!");
            return false;
        }

        // register our GreetingBot, perfere to use forward slashes in order to avoid specifying special chars by accident
        $botmanager->registerBotClass("com/examples/bots/greetingbot/GreetingBot");
        // load all bots from database
        $botmanager->loadBots();

        Log::info(self::$TAG, "starting the service");

        while(!$botsrvquery->terminate()) {
            try {
                $botmanager->update();
                $botsrvquery->update();
            }
            catch(\Exception $e) {
                // an exception during shutdown is not useful, ignore it!
                if (!$botsrvquery->terminate()) {
                    Log::warning(self::$TAG, "an exception occured: " . $e->getMessage());
                    //Log::warning(self::$TAG, "  backtrance: " . $e->getTraceAsString());
                }
                break;
            }
        }

        Log::info(self::$TAG, "shutdown the service");
        $botmanager->shutdown();
        $botsrvquery->shutdown();
        Log::info(self::$TAG, "bye");
    }
}

// let's go
$app = new App();
$app->start();
