<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\config;

/**
 *  Class for application configuration queries.
 * 
 *  NOTE: This class assumes that the main entry has already included the file 
 *        "config/Configuration.php" before.
 * 
 * @package   com\tsphpbots\config
 * @created   22th June 2016
 * @author    Botorabi
 */
abstract class Config {

    /**
     *
     * @var string  The framework version
     */
    protected static $FRAMEWORK_VERSION = "0.9.0";

    /**
     * Given an array and a token name return its value if it exists.
     * 
     * @param array $array        Array to search for the given token name
     * @param string $tokenName   Token name
     * @return Object              Return token value or null if the given name does not exist.
     */
    static protected function getTokenValue($array, $tokenName) {
        
        if (!isset($array[$tokenName])) {
            return null;
        }
        return $array[$tokenName];
    }

    /**
     * Get the framework version.
     * 
     * @return string   Framework version
     */
    public static function getFrameworkVersion() {

        return self::$FRAMEWORK_VERSION;
    }

    /**
     * Given a databank configuration token return its value if it exists.
     * For token names see "config/Configuration.php".
     * 
     * @param  string $configName      Name of configuration token
     * @return Object                  Return configuration value or null if the given name does not exist.
     */
    public static function getDB($configName) {

        return self::getTokenValue(\Configuration::$TSPHPBOT_CONFIG_DB, $configName);
    }
    
    /**
     * Given a TS3 server query configuration token return its value if it exists.
     * For token names see "config/Configuration.php".
     * 
     * @param string $configName      Name of configuration token
     * @return Object                 Return configuration value or null if the given name does not exist.
     */
    public static function getTS3ServerQuery($configName) {

        return self::getTokenValue(\Configuration::$TSPHPBOT_CONFIG_TS3SERVER_QUERY, $configName);
    }

    /**
     * Given a bot service configuration token return its value if it exists.
     * For token names see "config/Configuration.php".
     * 
     * @param string $configName    Name of configuration token
     * @return Object               Return configuration value or null if the given name does not exist.
     */
    public static function getBotService($configName) {

        return self::getTokenValue(\Configuration::$TSPHPBOT_CONFIG_BOT_SERVICE, $configName);
    }

    /**
     * Given a TS3 server query configuration token return its value if it exists.
     * For token names see "config/Configuration.php".
     * 
     * @param string $configName      Name of configuration token
     * @return Object                 Return configuration value or null if the given name does not exist.
     */
    public static function getWebInterface($configName) {

        return self::getTokenValue(\Configuration::$TSPHPBOT_CONFIG_WEB_INTERFACE, $configName);
    }
}
