<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\service;
use com\tsphpbots\bots\BotManager;
use com\tsphpbots\utils\Log;


/**
 * Bot related actions on server are implemented here.
 * 
 * @package   com\tsphpbots\service
 * @created   4th August 2016
 * @author    Botorabi
 */
class ServerBotAction {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "ServerBotActions";

    /**
     * @var BotManager The bot manager instance
     */
    protected $botManager = null;

    /**
     *
     * @var array  Command handler table
     */
    protected $commands = [];

    /**
     * Create the action handler.
     * 
     * @param BotManager $botManager    The bot manager
     */
    public function __construct($botManager) {
        $this->botManager = $botManager;
        // setup the command handlers
        $this->commands["botadd"]    = array($this->botManager, "notifyBotAdd");
        $this->commands["botupdate"] = array($this->botManager, "notifyBotUpdate");
        $this->commands["botdelete"] = array($this->botManager, "notifyBotDelete");
    }

    /**
     * Handle the request. If the request is no bot action then this
     * method does nothing and returns null.
     * 
     * @param string $reqMsg        The request text
     * @param string                JSON response if the command was a bot action
     *                              command, otherwise null is returned.
     */
    public function handleRequest($reqMsg) {
        if (is_null($reqMsg)) {
            return null;
        }

        $result = null;
        if ($this->parseCmd($reqMsg, $result)) {
            //Log::debug(self::$TAG, "bot command was dispatched");
        }
        return $result;
    }

    /**
     * Try to parse and recognize the command. If it was found then execute it. The result
     * will be stored in $res.
     * 
     * @param type $request The request
     * @param type $res     Result of a successful command execution.
     * @return boolean      true if the command was successfully parsed and executed.
     */
    protected function parseCmd($request, &$res) {
        $elems = explode(" ", $request);
        if (count($elems) < 3) {
            return false;
        }

        $cmd     = trim($elems[0]);
        $bottype = trim($elems[1]);
        $botid   = (int)trim($elems[2]);
        if (!is_numeric($botid)) {
            return false;
        }
        foreach($this->commands as $name => $handler) {
            if (strcmp($cmd, $name) === 0) {
                $handlerresult = $handler($bottype, $botid);
                $res = $this->createResponseBotAction($bottype, $botid, $handlerresult);
                return true;
            }
        }
        return false;
    }

    /**
     * Create a json response for bot updating.
     * 
     * @param string $botType   Bot Type
     * @param int $id           Bot ID
     * @param boolean $success  Pass true for success, false for fail
     * @return string           Bot update results.
     */
    protected function createResponseBotAction($botType, $id, $success) {
        return json_encode([
                "result" => $success ? "ok" : "nok",
                "id" => $id,
                "botType" => $botType
            ]);
    }
}
