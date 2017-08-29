<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

/**
 * This configuration is used for testing the framework.
 * 
 * NOTE: This configuration file must be included in the main
 *       app entry before any framework modules are loaded.
 *
 * First Created:  14th June 2016
 * Author:         Botorabi
 */
abstract class Configuration {

    //! TS3 Server Query related config
    public static  $TSPHPBOT_CONFIG_TS3SERVER_QUERY = [
        "host"          => "localhost",
        "userName"      => "serveradmin",       // put your TS3 user name here
        "password"      => "exffRAo3",          // put your TS3 user password here
        "hostPort"      => 10011,
        "vServerPort"   => 9987,
        "pollInterval"  => 2,                   // intervall of bot control steps in seconds
        "nickName"      => "TS3 PHP Bot"        // this is the name displayed in ts3 clients
    ];

    //! Databank account info (MySQL)
    public static $TSPHPBOT_CONFIG_DB = [
        "host"          => "localhost",
        "hostPort"      => 3306,
        "userName"      => "tsphpbot",
        "password"      => "tsphpbot",
        "dbName"        => "tsphpbot_test",
        "tablePrefix"   => ""                  // define a prefix for the table if you don't want an own db
    ];

    //! Web interface related info
    public static $TSPHPBOT_CONFIG_WEB_INTERFACE = [
        // Web service version.
        "version"        => "0.1.0",
        // Timeout for automatic logout while inactive (in minutes)
        "sessionTimeout" => 30,
        // App's main source directory. This path is relative to executing path (you should see the directory structure 'com/examples/...' there).
        "appSrc"         => "../src",
        // TeamSpeakPHPBots library's main source directory. You may have put it to /usr/local/share/TeamSpeakPHPBots, though. Who knows?
        "libSrc"         => "../src",
        // All web related assets are relative to this path
        "dirBase"        => "../src",
        "dirTemplates"   => "web/templates",
        "dirJs"          => "web/js",
        "dirStyles"      => "web/styles",
        "dirImages"      => "web/images",
        "dirLibs"        => "web/libs"
    ];

    //! Bot service related config
    public static  $TSPHPBOT_CONFIG_BOT_SERVICE = [
        // Bot serivce version
        "version"       => "0.1.0",
        // Bot service query IP
        "host"          => "127.0.0.1",
        // Bot service query port
        "hostPort"      => 12000,
        // Command line for starting the bot service, used by web service
        "cmdStart"      => "php greetingbot.php > botserver.log  2>&1 & echo $! > botserver.pid",
        // App's main source directory. This path is relative to executing path (you should see the directory structure 'com/examples/...' there).
        "appSrc"         => "./",
        // TeamSpeakPHPBots library's main source directory. You may have put it to /usr/local/share/TeamSpeakPHPBots, though. Who knows?
        "libSrc"         => "../TeamSpeakPHPBots/src"
    ];
}
