<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\utils;

/**
 * Class for logging.
 * 
 * NOTE: Do not try to create an instance of this class. Use its static methods.
 *
 * @package   com\tsphpbots\utils
 * @created   22th June 2016
 * @author    Botorabi
 */
abstract class Log {

    /**
     * This flag is usually used for tests in order to omit the HTML output.
     * 
     * @var boolean Enable/disable flag for output using 'echo'
     */
    protected static $enablePrintEcho = true;
    
    /**
     * @var boolean Enable/disyable the usage of error_log for log output
     */
    protected static $useErrorLog     = true;

    /**
     * Log the given message.
     * 
     * @param string $level   Log level
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    static protected function logMsg($level, $tag, $msg) {

        $ts = date('d.m.Y-H:i:s');
        $text = $ts . " " . $level . $tag . $msg;
        if (self::$useErrorLog == true) {
            error_log($text);
        }
        else {
            echo($text . "\n");
        }
    }

    /**
     * Use error_log for outputting logs. Default is using error_log for a
     * web application.
     * 
     * NOTE: For outputting via echo there is an own method called printEcho (see below).
     * 
     * @param boolean $en     Pass true for using error_log, otherwise echo is used.
     */
    public static function useErrorLog($en) {

        self::$useErrorLog = $en;
    }

    /**
     * Does the log output use error_log or echo?
     * 
     * @return boolean  Return true if error_log is used, false if echo is used.
     */
    public static function isErrorLogUsed() {

        return self::$useErrorLog;
    }

    /**
     * Enable/disable log messages using 'printEcho'.
     * Possible Usage: The output can be disabled for omitting HTML code during tests.
     * 
     * @param boolean $en     Enable/disable outputting messages by 'printEcho'
     */
    public static function enablePrintEcho($en) {

        self::$enablePrintEcho = $en;
    }

    /**
     * Are log messages using 'printEcho' enable?
     * 
     * @return boolean Return true if enabled, otherwise false.
     */
    public static function isEnabledPrintEcho() {

        return self::$enablePrintEcho;
    }

    /**
     * Log a message using 'echo'
     * 
     * @param string $msg     Message to log
     */
    public static function printEcho($msg) {

        if (self::$enablePrintEcho) {
            echo $msg;
        }
    }

    /**
     * Log a verbose message
     * 
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    public static function verbose($tag, $msg) {

        self::logMsg("[V]", "(" . $tag . ") ", $msg);
    }

    /**
     * Log a debug message
     * 
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    public static function debug($tag, $msg) {

        self::logMsg("[D]", "(" . $tag . ") ", $msg);
    }

    /**
     * Log an info message
     * 
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    public static function info($tag, $msg) {

        self::logMsg("[I]", "(" . $tag . ") ", $msg);
    }

    /**
     * Log an error message
     * 
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    public static function error($tag, $msg) {

        self::logMsg("[E]", "(" . $tag . ") ", $msg);
    }

    /**
     * Log a warning message
     * 
     * @param string $tag     Message tag  
     * @param string $msg     Message to log
     */
    public static function warning($tag, $msg) {

        self::logMsg("[W]", "(" . $tag . ") ", $msg);
    }

    /**
     * Log a message without any decoration.
     * 
     * @param string $msg     Message to log
     */
    public static function raw($msg) {

        self::logMsg("", "", $msg);
    }
}
