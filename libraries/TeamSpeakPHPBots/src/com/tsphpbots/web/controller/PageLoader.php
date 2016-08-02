<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\controller;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;
use com\tsphpbots\user\Auth;


/**
 * Class for loading a proper page controller depending on GET or POST parameters.
 * 
 * The URL parameter 'page' will be evaluated and determines which page to load.
 * If no page is specified then the default 'Main' page is loaded and displayed.
 * If a page needs the user to be logged in and the user is not logged in then
 * an authomatic redirect to 'Login' page is created. For every controller there 
 * may be a template with the same name plus 'html' file ending in template directory
 * which can be loaded automatically. For more information about controllers see
 * class BaseController.
 * 
 * The default page controllers Main.php, Login.php, and Logout.php can be overridden
 * by providing these files in one of the search paths which can be set in PageLoader.
 * 
 * Some URL examples: http://ip-address/?page=Main or http://ip-address/?page=UserAdmin&id=42
 *  
 * @package   com\tsphpbots\web\controller
 * @created   22th June 2016
 * @author    Botorabi
 */
class PageLoader {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "PageLoader";

    /**
     * @var string Main page's controller name
     */
    protected static $defaultPageName = "Main";

    /**
     * @var string Login page's controller name
     */
    protected static $loginPageName   = "Login";

    /**
     *
     * @var array  Search paths for finding page controller modules
     */
    protected $moduleSearchPaths = [];

    /**
     * Set an array of search paths for finding controller modules.
     * This is used by an application in order to provide own modules, which can
     * also override the default modules such as Main.php, PageNotFound.php etc.
     * 
     * @param array $paths      Search paths
     */
    public function setModuleSearchPaths(array $paths) {
        $this->moduleSearchPaths = $paths;
    }

    /**
     * Return the search paths for finding controller modules.
     * 
     * @return array         Search paths
     */
    public function getModuleSearchPaths() {
        return $this->moduleSearchPaths;
    }

    /**
     * Load a proper page controller depending on the GET or POST parameter.
     * The page name must be given with "page" parameter.
     * If there is no "page" parameter the the default page is used.
     * 
     * The special GET or POST parameter '?page=timeLeft' delivers (while logged in)
     * the time left until an automatic logout because of inactivity occurrs.
     * 
     * @param array $GET      GET parameters got from PHP interpreter
     * @param array $POST     POST parameters got from PHP interpreter
     * @return boolean        Return true if the page could be loaded, otherwise false
     */
    public function load(array $GET, array $POST) {

        $method = "GET";
        if (isset($GET["page"])) {
            $classname = $GET["page"];
        }
        else if (isset($POST["page"])) {
            $classname = $POST["page"];
            $method = "POST";
        }
        else {
            $classname = self::$defaultPageName;
        }

        // this is a special request and delivers the time left while logged in
        //  ?page=timeLeft
        if (strcmp($classname, "timeLeft") === 0) {
            $response = json_encode(["timeLeft" => Auth::leftTime()]);
            Log::printEcho($response);
            return true;
        }

        if (strlen($classname) == 0) {
            $classname = self::$defaultPageName;            
        }

        //Log::verbose(self::$TAG, $classname . ",\nGET:" . print_r($GET, true) . ",\nPOST: " . print_r($POST, true));

        $classpath = $this->getClassPath($classname);

        if (is_null($classpath)) {
            Log::warning(self::$TAG, "Page could not be found: " . $classname);
            return false;
        }

        //! NOTE it seems that we have no chance to recover the case that the class could not be found here!
        //       any autoloader thrown exceptions will not arrive here!
        $page = new $classpath();

        if (!in_array($method, $page->getAccessMethods())) {
            Log::warning(self::$TAG, "Invalid access method used for page: " . $classname);
            return false;
        }

        if ($page->getNeedsLogin() && !Auth::isloggedIn()) {
            $classpath = $this->getClassPath(self::$loginPageName);
            $page = new $classpath();
        }

        $parameters = ["GET" => $GET, "POST" => $POST];
        $page->view($parameters);
        return true;
    }

    /**
     * Try to find the given module in search paths. Return null if it could not be found.
     * 
     * @param string $moduleName    Module name to search for (without .php ending)
     * @return string               Full path of the module (without module name),
     *                               or null if not found.
     */
    protected function findFilePath($moduleName) {

        if (is_null($this->moduleSearchPaths)) {
            return null;
        }
        $basedir = Config::getWebInterface("appSrc");
        foreach($this->moduleSearchPaths as $path) {
            $fullpath = $path . "/" . $moduleName;
            $fullpath = $basedir . "/" . str_replace("\\", "/", $fullpath);
            if (file_exists($fullpath . ".php")) {
                return str_replace("/", "\\", $path);
            }
        }
        return null;
    }

    /**
     * Given a controller class name return its path ready for loading.
     * The defined search paths are checked and if not successful then the
     * directory of this module is searched for the module.
     * 
     * @param string $className   Class name
     * @return string             Full path of the class file for loading, or null 
     *                            if the class name could not be determined.
     */
    protected function getClassPath($className) {

        // first, try to search for the module in defined search paths
        $filesearchpath = $this->findFilePath($className);
        if (!is_null($filesearchpath)) {
            return $filesearchpath . "\\" . $className; 
        }

        // fallback to the directory where this loader module resides
        $class    = __CLASS__;
        $pos      = strrpos($class, "\\", -1);
        $basedir  = substr($class, 0, $pos);
        $path     = str_replace("/", "\\", $basedir);

        // check if the file exists
        $fullpath = getcwd() . DIRECTORY_SEPARATOR . Config::getWebInterface("libSrc") .
                    DIRECTORY_SEPARATOR . $basedir . "\\" . $className . ".php";
        $fullpath = str_replace("\\", DIRECTORY_SEPARATOR, $fullpath);

        //Log::verbose(self::$TAG, "class: " . $class . ", basedir: " . $basedir . ", path: " . $path . ", fullpath: " . $fullpath);

        if (file_exists($fullpath) === false) {
            return null;
        }

        return $path . "\\" . $className;
    }
}
