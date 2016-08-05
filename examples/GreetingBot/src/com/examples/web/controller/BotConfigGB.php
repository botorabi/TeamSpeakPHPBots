<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\web\controller;
use com\examples\bots\greetingbot\GreetingBotModel;
use com\tsphpbots\web\core\BaseController;
use com\tsphpbots\user\Auth;
use com\tsphpbots\utils\Log;

/**
 * Page controller for bot GreetingBot
 * 
 * @created:  22th June 2016
 * @author:   Botorabi
 */
class BotConfigGB extends BaseController {

    /**
     * @var string Log tag
     */
    protected static $TAG = "BotConfigGB";

    /**
     * @var string Class name used for automatically find the proper template
     */
    public $renderClassName = "BotConfigGB";

    protected $renderMainClass  = "Main";

    protected $botSummaryFields = ["id", "botType", "name", "description", "active"];

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
     * Create a view for bot configuration.
     * 
     * @param array $parameters  URL parameters such as GET or POST
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

        if (isset($params["list"])) {
            $this->createRespJsonAllBots();
        }
        else if (isset($params["id"])) {
            $this->createRespJsonBot($params["id"]);
        }
        else if (isset($params["create"])) {
            $this->cmdCreateBot($params);
        }
        else if (isset($params["update"])) {
            $this->cmdUpdateBot($params);
        }
        else if (isset($params["delete"])) {
            $this->cmdDeleteBot($params);
        }
        else {
            $this->renderView($this->renderClassName, null);
        }
    }

    /**
     * Create a JSON response for all available bots.
     * 
     * @return boolean true if successful, otherweise false
     */
    protected function createRespJsonAllBots() {

        $response = [];
        $bot = new GreetingBotModel();
        $botids = $bot->getAllObjectIDs();
        if (is_null($botids)) {
            Log::error(self::$TAG, "Could not access the database!");
            Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
            return false;
        }

        foreach($botids as $id) {
            $botcfg = new GreetingBotModel($id);
            if (is_null($botcfg)) {
                Log::warning(self::$TAG, "Could not access the database!");
                Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
                return false;
            }
            $record = [];
            foreach($botcfg->getFields() as $field => $value) {
                if (!in_array($field, $this->botSummaryFields)) {
                    continue;
                }
                $record[$field] = $value;
            }
            $response[] = $record;
        }
        $json = json_encode(["result" => "ok", "data" => $response]);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a JSON response for a bot configuration given its ID.
     * 
     * @param int $id   Bot ID
     * @return boolean  true if successful, otherweise false
     */
    protected function createRespJsonBot($id) {
        
        $bot = new GreetingBotModel($id);
        if (is_null($bot->getObjectID())) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
            return false;
        }

        $response = [];
        foreach($bot->getFields() as $field => $value) {
            $response[$field] = $value;
        }

        $json = json_encode(["result" => "ok", "data" => $response]);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a new bot.
     * 
     * @param array $params   Page parameters containing the bot configuration
     */
    protected function cmdCreateBot($params) {

        $bot = new GreetingBotModel();
        $bot->setFieldValue("name", $this->getParamString($params, "name", "New Bot"));
        $bot->setFieldValue("description", $this->getParamString($params, "description", ""));
        $bot->setFieldValue("active", 1);
        $bot->setFieldValue("greetingText", $this->getParamString($params, "greetingText", ""));
        $id = $bot->create();
        
        if (is_null($id)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
        }
        else {
            $this->createRespJsonBot($id);
        }
    }

    /**
     * Update a bot. The bot ID must be given in parameter field "update".
     * 
     * @param array $params      Page parameters, expected bot ID must be in field "update".
     */
    protected function cmdUpdateBot($params) {

        if (!isset($params["update"])) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input"]));
            return;
        }

        $botid = $params["update"];
        $bot = new GreetingBotModel($botid);
        if ($bot->getObjectID() === 0) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid ID"]));
            return;
        }

        if (isset($params["name"])) {
            $bot->setFieldValue("name", $this->getParamString($params, "name", ""));
        }
        if (isset($params["description"])) {
            $bot->setFieldValue("description", $this->getParamString($params, "description", ""));
        }
        if (isset($params["active"])) {
            $bot->setFieldValue("active", ($this->getParamNummeric($params, "active", 1) === 1) ? 1 : 0);
        }
        if (isset($params["greetingText"])) {
            $bot->setFieldValue("greetingText", $this->getParamString($params, "greetingText", ""));
        }

        if ($bot->update() === false) {
             Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
        }
        else {
            $json = json_encode(["result" => "ok", "data" => ["id" => $bot->getObjectID()]]);
            Log::printEcho($json);
        }
    }

    /**
     * Delete a bot. The bot ID must be given in parameter field "delete".
     * 
     * @param array $params  Page parameters, expected bot ID must be in field "delete".
     */
    protected function cmdDeleteBot($params) {

        if (!isset($params["delete"])) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input"]));
            return;
        }

        $botid = $params["delete"];
        $bot = new GreetingBotModel($botid);
        if ($bot->delete() === false) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid ID"]));
        }
        else {
            Log::printEcho(json_encode(["result" => "ok", "data" => ["id" => $botid]]));
        }
    }
}