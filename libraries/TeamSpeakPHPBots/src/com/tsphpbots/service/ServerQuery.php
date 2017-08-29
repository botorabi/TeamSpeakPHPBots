<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\service;
use com\tsphpbots\service\ServerBotAction;
use com\tsphpbots\bots\BotManager;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;

/**
 * Bot service's query server providing a TCP based query mechanism which delivers
 * information about the bot service.
 * 
 * @package   com\tsphpbots\service
 * @created   21st July 2016
 * @author    Botorabi
 */
class ServerQuery {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "ServerQuery";

    /**
     * @var BotManager The bot manager instance
     */
    protected $botManager = null;

    /**
     * @var ServerBotAction A handler for all bot actions
     */
    protected $botActionHandler = null;

    /**
     * @var Object Socket
     */
    protected $socket = null;

    /**
     * @var string  IP address
     */
    protected $addr = '';
    
    /**
     * @var int IP port
     */
    protected $port = 0;

    /**
     * @var boolean Terminate flag
     */
    protected $term = false;

    /**
     * Initialize the query server by starting a non-blocking tcp listener.
     * 
     * @param BotManger $botManager  The bot manager
     * @param string $addr           TCP address for the bot service query server, defaults to bot service configuration
     * @param int $port              TCP port number for the bot service query server, defaults to bot service configuration
     * @return boolean               Return true if successful, otherwise false
     */
    public function initialize(BotManager $botManager, $addr = null, $port = null) {

        $this->botManager = $botManager;
        $this->botActionHandler = new ServerBotAction($botManager);
        
        $this->addr = is_null($addr) ? Config::getBotService("host") : $addr;
        $this->port = is_null($port) ? Config::getBotService("hostPort") : $port;

        $this->socket = socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            Log::warning(self::$TAG, "could not setup server socket, reason: " . socket_strerror(socket_last_error()));
            return false;
        }

        socket_set_option($this->socket, SOL_SOCKET, SO_REUSEADDR, 1);
        socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
        socket_set_nonblock($this->socket);
 
        if (socket_bind($this->socket, $this->addr, $this->port) === false) {
            Log::warning(self::$TAG, "could not bind the server socket, reason: " . socket_strerror(socket_last_error()));
            return false;
        }

        if (socket_listen($this->socket, 10) === false) {
            Log::warning(self::$TAG, "server socket cannot start listening, reason: " . socket_strerror(socket_last_error()));
            return false;
        }

        return true;
    }

    /**
     * Shutdown the server interface.
     */
    public function shutdown() {
        if ($this->socket) {
            socket_close($this->socket);
        }
        $this->socket = null;
    }

    /**
     * Poll incoming connections.
     */
    public function update() {
        if (is_null($this->socket)) {
            return;
        }

        do {
            $connection = @socket_accept($this->socket);
            if ($connection === false) {
                break;
            }
            //Log::debug(self::$TAG, "client connected");

            $reqmsg = @socket_read($connection, 256, PHP_NORMAL_READ);
            if ($reqmsg === false) {
                continue;
            }
            
            //Log::debug(self::$TAG, "client request: " . $reqmsg);

            $this->responseToClient($connection, trim($reqmsg));
            @socket_close($connection);
        }
        while(true);
    }

    /**
     * Check if the service termination command was received ('stop' command).
     * 
     * @return boolean  Return true if the termination command was received, otherwise false.
     */
    public function terminate() {
        return $this->term;
    }

    /**
     * Responde to client's request.
     * 
     * @param Socket $client        Client requesting for a response
     * @param string $reqMsg        The request text
     */
    protected function responseToClient($client, $reqMsg) {
        if (is_null($this->socket) || is_null($reqMsg)) {
            return;
        }

        // status info is requested periodically, avoid its output
        if (strcmp($reqMsg, "status") !==0) {
            Log::debug(self::$TAG, "got request: '" . $reqMsg . "'");
        }

        // check for bot actions
        $response = $this->botActionHandler->handleRequest($reqMsg);
        if (!is_null($response)) {
            socket_write($client, $response . "\r");
        }
        else {
            switch($reqMsg) {
                case 'version':
                    socket_write($client, $this->createResponseVersion() . "\r");
                    break;
                case 'status':
                    socket_write($client, $this->createResponseStatus() . "\r");
                    break;
                case 'stop':
                    socket_write($client, $this->createResponseStop() . "\r");
                    $this->term = true;
                    break;
                default:
                    Log::warning(self::$TAG, "received an unexpected bot query server request: " . $reqMsg);
            }
        }
    }

    /**
     * Create a json response for bot service version.
     * 
     * @return string  Bot service version
     */
    protected function createResponseVersion() {
        return json_encode([
                "version" => Config::getBotService("version")
            ]);
    }

    /**
     * Create a json response for bot service status.
     * 
     * @return string  Bot service status
     */
    protected function createResponseStatus() {
        return json_encode([
                "version" => Config::getBotService("version"),
                "status" => "up"
            ]);
    }

    /**
     * Create a json response for bot service stopping.
     * 
     * @return string  Bot service stopping results.
     */
    protected function createResponseStop() {
        return json_encode([
                "stop" => "ok"
            ]);
    }
}
