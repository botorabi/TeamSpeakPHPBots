<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\web\controller;
use com\tsphpbots\web\core\BaseController;
use com\tsphpbots\service\ClientQuery;
use com\tsphpbots\user\User;
use com\tsphpbots\user\Auth;
use com\tsphpbots\utils\Log;
use com\tsphpbots\config\Config;


/**
 * BotServer Page controller
 * 
 * @created:  21st July 2016
 * @author:   Botorabi
 */
class BotServer extends BaseController {

    /**
     * @var string Log tag
     */
    protected static $TAG = "BotServer";

    /**
     * @var string Class name used for automatically find the proper template
     */
    protected $renderClassName   = "BotServer";

    protected $renderMainClass   = "Main";
    
    protected $botSummaryFields  = ["id", "name", "description", "active"];

    protected $loggedInUser      = null;

    /**
     * Return true if the user needs a login for this page.
     * 
     * @return boolean      true if login is needed for the page, othwerwise false.
     */
    public function getNeedsLogin() {
        return true;
    }

    /**
     * Allowed access methods (e.g. ["GET", "POST"]).
     * 
     * @return string array     Array of access method names.
     */
    public function getAccessMethods() {
        return ["GET", "POST"];
    }

    /**
     * Create a view for user administration.
     * 
     * @param $parameters  URL parameters such as GET or POST
     */
    public function view($parameters) {

        if (!Auth::isLoggedIn()) {
            $this->redirectView($this->renderMainClass);
            return;
        }

        // check the params
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

        $this->loggedInUser = new User(Auth::getUserID());
        if (is_null($this->loggedInUser)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "internal error, user not found!"]));
            return;
        }

        if (isset($params["status"])) {
            $this->cmdServerStatus();
        }
        else if (isset($params["start"])) {
            $this->cmdStartBotServer();
        }
        else if (isset($params["stop"])) {
            $this->cmdStopBotServer();
        }
        else if (isset($params["add"])) {
            $this->cmdBotAdd($params["botType"], $params["add"]);
        }
        else if (isset($params["update"])) {
            $this->cmdBotUpdate($params["botType"], $params["update"]);
        }
        else if (isset($params["delete"])) {
            $this->cmdBotDelete($params["botType"], $params["delete"]);
        }
        else if (isset($params["msg"])) {
            $this->cmdBotMessage($params["botType"], $params["msg"], $params["text"]);
        }
        else {
            $this->renderView($this->renderClassName, null);
        }
    }

    /**
     * Create a JSON response for the bot server status.
     */
    protected function cmdServerStatus() {

        //Log::debug(self::$TAG, "get bot service status...");

        $servicequery = new ClientQuery;
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $state = $servicequery->getStatus();
        $servicequery->shutdown();
        
        if ($state) {
            $res = ["result" => "ok", "data" => json_decode($state)];
            Log::printEcho(json_encode($res));
        }
        else {
            Log::printEcho(json_encode(["result" => "nok"]));
        }
    }

    /**
     * Start the bot server. Ignores the call if the server is already running.
     */
    protected function cmdStartBotServer() {

        Log::debug(self::$TAG, "starting the bot service...");

        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() !== false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }
        $state = $servicequery->getStatus();
        // check if the server is already running
        if (!is_null($state)) {
            Log::debug(self::$TAG, "bot service is already running!");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }
        $startcmd = Config::getBotService("cmdStart");
        Log::debug(self::$TAG, "starting: " . $startcmd);
        // on ms windows
        if (strtoupper(substr(PHP_OS, 0, 3)) === 'WIN') {
            $out = "";
            $ret = 0;
            exec("'" . $startcmd . "'", $out, $ret);
        }
        else {
            // on linux or mac
            shell_exec($startcmd);
        }
        Log::printEcho(json_encode(["result" => "ok"]));
    }

    /**
     * Stop the bot server.
     */
    protected function cmdStopBotServer() {

        Log::debug(self::$TAG, "stopping the bot service...");
              
        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $state = $servicequery->stopService();

        if (is_null($state)) {
            Log::debug(self::$TAG, "bot service seems not running!");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        Log::printEcho(json_encode(["result" => "ok"]));
    }

    /**
     * Add a bot given its type and ID.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     */
    protected function cmdBotAdd($botType, $id) {

        Log::debug(self::$TAG, "adding bot, id: " . $id);

        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $result = $servicequery->botAdd($botType, $id);

        if (is_null($result)) {
            Log::debug(self::$TAG, " could not add bot");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        Log::printEcho(json_encode(["result" => "ok"]));
    }

    /**
     * Update the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     */
    protected function cmdBotUpdate($botType, $id) {

        Log::debug(self::$TAG, "updating bot, id: " . $id);

        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $result = $servicequery->botUpdate($botType, $id);

        if (is_null($result)) {
            Log::debug(self::$TAG, " could not update bot");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        Log::printEcho(json_encode(["result" => "ok"]));
    }

    /**
     * Delete the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     */
    protected function cmdBotDelete($botType, $id) {

        Log::debug(self::$TAG, "deleting bot, id: " . $id);

        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $result = $servicequery->botDelete($botType, $id);

        if (is_null($result)) {
            Log::debug(self::$TAG, " could not delete bot");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        Log::printEcho(json_encode(["result" => "ok"]));
    }

    /**
     * Send a message to the bot given its ID and type.
     * 
     * @param string $botType       Bot type
     * @param int $id               Bot ID
     * @param string $text          Message text
     */
    protected function cmdBotMessage($botType, $id, $text) {

        Log::debug(self::$TAG, "sending message to bot, id: " . $id);

        $servicequery = new ClientQuery;
        $servicequery->connect();
        if ($servicequery->connect() === false) {
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        $result = $servicequery->botMessage($botType, $id, $text);

        if (is_null($result)) {
            Log::debug(self::$TAG, " could not send message to bot");
            Log::printEcho(json_encode(["result" => "nok"]));
            return;
        }

        Log::printEcho(json_encode(["result" => "ok"]));
    }
}
