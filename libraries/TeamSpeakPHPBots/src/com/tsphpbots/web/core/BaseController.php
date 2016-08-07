<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\core;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;
use com\tsphpbots\user\Auth;

/**
 * Base class of all kinds of page controllers. This class provides various
 * functionalities such as page template and include resolution, HTML variable
 * resolution, and view rendering.
 * 
 * @package   com\tsphpbots\web\core
 * @created   22th June 2016
 * @author    Botorabi
 */
abstract class BaseController {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "BaseController";

    /**
     * @var string Default location of library's template folder
     */
    protected $defaultTemplateDir = "web/templates";

    /**
     * @var string Template property <!--dirBase-->
     */
    protected $dirBase          = "";
    /**
     * @var string Template property <!--dirTemplates-->
     */
    protected $dirTemplates     = "";
    /**
     * @var string Template property <!--dirImages-->
     */
    protected $dirImages        = "";
    /**
     * @var string Template property <!--dirStyles-->
     */
    protected $dirStyles        = "";
    /**
     * @var string Template property <!--dirJs-->
     */
    protected $dirJs            = "";
    /**
     * @var string Template property <!--dirLibs-->
     */
    protected $dirLibs          = "";
    /**
     * @var string Template property <!--webVersion-->
     */
    protected $webVersion       = "";
    /**
     * @var string Template property <!--frameworkVersion-->
     */
    protected $frameworkVersion = "";

    /**
     * Construct the controller
     */
    function __construct() {
        $this->dirBase = Config::getWebInterface("dirBase");
        if (strlen($this->dirBase) > 0) {
            $this->dirBase .= DIRECTORY_SEPARATOR;
        }
        $this->dirTemplates     = $this->dirBase . Config::getWebInterface("dirTemplates");
        $this->dirImages        = $this->dirBase . Config::getWebInterface("dirImages");
        $this->dirStyles        = $this->dirBase . Config::getWebInterface("dirStyles");
        $this->dirJs            = $this->dirBase . Config::getWebInterface("dirJs");
        $this->dirLibs          = $this->dirBase . Config::getWebInterface("dirLibs");
        $this->webVersion       = Config::getWebInterface("version");
        $this->frameworkVersion = Config::getFrameworkVersion();
    }

    /**
     * Following methods are needed by page loader
     * and must be implemented by derived classes.
     * ###########################################
     */

    /**
     * Return true if the user needs a login for this page.
     * 
     * @return boolean      true if login is needed for the page, othwerwise false.
     */
    abstract public function getNeedsLogin();

    /**
     * Allowed access methods (e.g. ["GET", "POST"]).
     * 
     * @return string array     Array of access method names.
     */
    abstract public function getAccessMethods();

    /**
     * ###########################################
     */


    /**
     * Given an array containing GET and POST sub-arrays, combine them to one single array.
     * This utility method is used when it does not matter if a parameter was given by POST or GET.
     * 
     * @param array $parameters  URL parameters possibly containing GET and POST
     * @return array             Flat array containing GET and POST parameters
     */
    public function combineRequestParameters($parameters) {
        $params = [];
        if (isset($parameters["POST"])) {
            foreach($parameters["POST"] as $param => $val) {
                $params[$param] = $val;
            }
        }
        if (isset($parameters["GET"])) {
            foreach($parameters["GET"] as $param => $val) {
                $params[$param] = $val;
            }
        }
        return $params;
    }

    /**
     * Render the content of a template file basing on the given controller class name and
     * used properties. Properties are replaced in the template.
     * 
     * First the application specific template directory is searched, if not successful then
     * the default library's template directory is searched.
     * 
     * @param  $className   View class name used for finding the template file.
     * @param  $properties  Properties which should be used for generating the view.
     * @return              true if the page was rendered successfully, otherwise false.
     */
    public function renderView($className, $properties = null) {
        // first, check the app directory
        $template = $this->dirTemplates . "/" . $className . ".html";
        $content = $this->getFileContent($template);
        // if the template was not found in app directory, then try the default library directory
        if ($content === null) {
            $template = Config::getWebInterface("libSrc") . "/" . $this->defaultTemplateDir . "/" . $className . ".html";
            $content = $this->getFileContent($template);
            if ($content === null) {
                Log::error(self::$TAG, "could not find a template for module: " . $className);
                return false;
            }
        }
        $this->processContent($content, $properties);
        Log::printEcho($content);
        return true;
    }

    /**
     * Redirect the view to a new location.
     * 
     * @param type $className       Name of new view class
     * @param type $properties      Properties which should be used for generating the view.
     */
    public function redirectView($className, $properties = null) {
        $props = "";
        if ($properties) {
            foreach($properties as $prop => $value){
                $props .= "&" . $prop . "=" . $value;
            }
        }

        $html  = "<!DOCTYPE html><html><head><meta http-equiv='refresh'";
        $html .= "content='0; url=?page=" .$className . $props . "'>";
        $html .= "</head><body></body></html>";
        
        Log::printEcho($html);
    }

    /**
     * Process the template and replace given property keys by their values.
     * In addition some standard properties are replaced too.
     * 
     * @param $content         Template content
     * @param $properties      Properties which are filled in the template
     */
    protected function processContent(&$content, $properties) {

        // first put the includes into the content
        $this->processIncludes($content);

        if ($properties) {
            foreach($properties as $prop => $value) {
                $this->replaceProperty($content, $prop, $value);
            }
        }

        // replace the standard props
        $this->replaceProperty($content, "webVersion",       $this->webVersion);
        $this->replaceProperty($content, "frameworkVersion", $this->frameworkVersion);
        $this->replaceProperty($content, "dirTemplates",     $this->dirTemplates);
        $this->replaceProperty($content, "dirImages",        $this->dirImages);
        $this->replaceProperty($content, "dirStyles",        $this->dirStyles);
        $this->replaceProperty($content, "dirJs",            $this->dirJs);
        $this->replaceProperty($content, "dirLibs",          $this->dirLibs);
        $this->replaceProperty($content, "lastUpdate",       time());
        $this->replaceProperty($content, "SID",              htmlspecialchars(session_id()));
        $this->replaceProperty($content, "userName",         htmlspecialchars(Auth::getUserName()));
    }

    /**
     * Put the include files into main content.
     * The includes have following format:
     * 
     *    <!--INCLUDE:filename-->
     * 
     * The file must be realative to template directory.
     * 
     * If the file could not be found in application's template directory then
     * it is searched in library's default template directory (see configuration).
     * 
     * @param  $content     Content which is enhanced by includes
     */
    public function processIncludes(&$content) {
        
        $INCLUDE_TOKEN_BEGIN = "<!--INCLUDE:";
        $INCLUDE_TOKEN_END   = "-->";
        $pos = 0;
        while (($pos = strpos($content, $INCLUDE_TOKEN_BEGIN)) !== false) {
            $posend = strpos($content, $INCLUDE_TOKEN_END, $pos);
            if ($posend != false) {
                $str2subst = substr($content, $pos, $posend + strlen($INCLUDE_TOKEN_END) - $pos);

                $incname  = substr($content,
                                   $pos    + strlen($INCLUDE_TOKEN_BEGIN), 
                                   $posend - strlen($INCLUDE_TOKEN_BEGIN) - $pos);

                //Log::verbose(self::$TAG, "INCLUDE: '" . $str2subst . "' replaced by FILE: '" . $incname);

                $tname = $this->dirTemplates . "/" . $incname;
                $inc = $this->getFileContent($tname);
                if ($inc) {
                    $content = str_replace($str2subst, $inc, $content);
                }
                else {
                    $tname = Config::getWebInterface("libSrc") . "/" . $this->defaultTemplateDir . "/" . $incname;
                    $inc = $this->getFileContent($tname);
                    if ($inc) {
                        $content = str_replace($str2subst, $inc, $content);
                    }
                    else {
                        Log::error(self::$TAG, "could not find include file: " . $incname);
                        break;
                    }
                }
            }
        }
    }

    /**
     * Get the content of a given file.
     * 
     * @param $fileName     The file name
     * @return              The file content, or null if the file cannot be read.
     */
    protected function getFileContent($fileName) {
        $file = @fopen($fileName, "r");
        if (!$file) {
            return null;
        }

        $content = fread($file, filesize($fileName));
        fclose($file);
        return $content;
    }

    /**
     * Replace one single key by its value. Keys are addressed in the template files
     * in following format: <!--KEY NAME-->
     * 
     * @param $content     The content to modify
     * @param $key         Key which will be replaced by its value
     * @param $value       Key value
     */
    protected function replaceProperty(&$content, $key, $value) {
        $content = str_replace("<!--" . $key . "-->", $value, $content);
    }

    /**
     * Given a parameter array, return the string value of a parameter.
     * 
     * @param $params       Parameter array
     * @param $paramName    Parameter name
     * @param $defaultValue Parameter default value
     * @return              Parameter value if successful,
     *                       or $defaultValue if the parameter does not exist.
     */
    protected function getParamString(array $params, $paramName, $defaultValue) {
        $val = $defaultValue;
        if (isset($params[$paramName])) {
            $val = (string)$params[$paramName];
        }
        return $val;
    }

    /**
     * Given a parameter array, return the nummeric value of a parameter.
     * 
     * @param $params       Parameter array
     * @param $paramName    Parameter name
     * @param $defaultValue Parameter default value
     * @return              Parameter value if successful,
     *                       or $defaultValue if the parameter does not exist.
     */
    protected function getParamNummeric(array $params, $paramName, $defaultValue) {
        $val = $defaultValue;
        if (isset($params[$paramName]) &&
            is_numeric($params[$paramName])) {
            $val = (int)$params[$paramName];
        }
        return $val;
    }

    /**
     * Given a parameter array, return the nummeric values of a parameter which
     * is expected to be in form of a comma-separated string of nummerics, e.g.:
     * 
     *   some IDs = "1, 3, 45"
     * 
     * @param $params       Parameter array
     * @param $paramName    Parameter name
     * @param $defaultValue Parameter default value
     * @return              Parameter value array if successful,
     *                       or $defaultValue if the parameter does not exist.
     */
    protected function getParamArrayNummeric(array $params, $paramName, $defaultValue) {
        $values = [];
        if (isset($params[$paramName])) {
            $str = $params[$paramName];
            $ids = explode(",", $str);
            foreach($ids as $id) {
                if (is_numeric($id)) {
                    $values[] = (int)$id;
                }
            }
        }
        else {
            return $defaultValue;
        }
        return $values;
    }
}