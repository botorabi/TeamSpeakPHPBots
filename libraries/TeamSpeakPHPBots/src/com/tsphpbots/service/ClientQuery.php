<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\service;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;

/**
 * Bot service's query client providing a TCP based query mechanism which delivers
 * information about the bot service.
 * 
 * @package   com\tsphpbots\service
 * @created   21st July 2016
 * @author    Botorabi
 */
class ClientQuery {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "ClientQuery";

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
     * Connects the query server.
     * 
     * @param string $addr      TCP address for the bot service query server, defaults to bot service configuration
     * @param int $port         TCP port number for the bot service query server, defaults to bot service configuration
     * @return boolean          Return true if successful, otherwise false
     */
    public function connect($addr = null, $port = null) {
        $this->addr = is_null($addr) ? Config::getBotService("host") : $addr;
        $this->port = is_null($port) ? Config::getBotService("hostPort") : $port;

        $this->socket = @socket_create(AF_INET, SOCK_STREAM, SOL_TCP);

        if ($this->socket === false) {
            Log::warning(self::$TAG, "could not setup client socket, reason: " . socket_strerror(socket_last_error()));
            return false;
        }

        @socket_set_block($this->socket);
        @socket_set_option($this->socket, SOL_SOCKET, SO_SNDTIMEO, array('sec' => 1, 'usec' => 0)); 
 
        if (@socket_connect($this->socket, $this->addr, $this->port) === false) {
            //Log::warning(self::$TAG, "could not connect the server, reason: " . socket_strerror(socket_last_error()));
            return false;
        }

        return true;
    }

    /**
     * Shutdown the server interface.
     */
    public function shutdown() {
        if ($this->socket) {
            @socket_close($this->socket);
        }
        $this->socket = null;
    }

    /**
     * Request the service for an information and return its reponse.
     * 
     * @param string $request       Request text.
     * @return string               Response text if sucessful, otherwise null.
     */
    protected function getServiceResponse($request) {
        if (is_null($this->socket)) {
            Log::error(self::$TAG, "socket was not setup before!");
            return null;
        }
        @socket_write($this->socket, $request . "\r");
        $response = @socket_read($this->socket, 256, PHP_NORMAL_READ);
        if ($response === false) {
            //Log::debug(self::$TAG, "could not get bot service response");
            return null;
        }
        return $response;
    }

    /**
     * Get the bot service version.
     * 
     * @return string       The service version if successful, otherwise null.
     */
    public function getVersion() {
        return $this->getServiceResponse("version");
    }

    /**
     * Get the bot service status information (a json formated string).
     * 
     * @return string       The service state if successful, otherwise null.
     */
    public function getStatus() {
        return $this->getServiceResponse("status");
    }

    /**
     * Stop the bot service.
     * 
     * @return string       The stop attempt's result if successful, otherwise null.
     */
    public function stopService() {
        return $this->getServiceResponse("stop");        
    }

    /**
     * Update the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     * @return string               The bot update attempt's result if successful, otherwise null.
     */
    public function botUpdate($botType, $id) {
        return $this->getServiceResponse("botupdate " . $botType . " " . $id);
    }

    /**
     * Add the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     * @return string               The bot update attempt's result if successful, otherwise null.
     */
    public function botAdd($botType, $id) {
        return $this->getServiceResponse("botadd " . $botType . " " . $id);
    }

    /**
     * Delete the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     * @return string               The bot update attempt's result if successful, otherwise null.
     */
    public function botDelete($botType, $id) {
        return $this->getServiceResponse("botdelete " . $botType . " " . $id);
    }
    
    /**
     * Send a message to the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     * @param string $text          Message text
     * @return string               The bot update attempt's result if successful, otherwise null.
     */
    public function botMessage($botType, $id, $text) {
        return $this->getServiceResponse("botmsg " . $botType . " " . $id . " " . $text);
    }
}
