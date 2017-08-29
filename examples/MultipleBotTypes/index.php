<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */


error_reporting (E_ALL);
ini_set("display_errors", 1);

require_once("config/Configuration.php");

use com\tsphpbots\web\core\PageLoader;
use com\tsphpbots\utils\Log;

/**
 * Main entry of MultipleBotType example's web service
 * 
 * @created:  4nd August 2016
 * @author:   Botorabi
 */
class App {

    /**
     *
     * @var string Used for logs
     */
    protected static $TAG = "Index";


    /**
     *
     * @var array  Search paths for finding php files
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
        //! Main source directory used in autoloader
        $this->SEARCH_PATHS[] = Configuration::$TSPHPBOT_CONFIG_WEB_INTERFACE["appSrc"];
        //! The path of TS3PHPBots library
        $this->SEARCH_PATHS[] = Configuration::$TSPHPBOT_CONFIG_WEB_INTERFACE["libSrc"];


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
     * Start processing the request.
     */
    public function start() {
        // start a session
        session_start();
        try {
            $pageloader = new PageLoader();

            // add the app's web controller directory to page loader's search paths.
            $paths = ['com/examples/web/controller'];
            $pageloader->setModuleSearchPaths($paths);

            if (!$pageloader->load($_GET, $_POST)) {
                $pageloader->load(["page" => "PageNotFound"], []);
            }
        }
        catch(Exception $e) {
            Log::error("INDEX", "An error occured: " . $e->getMessage());
        }
    }
}

$app = new App();
$app->start();
