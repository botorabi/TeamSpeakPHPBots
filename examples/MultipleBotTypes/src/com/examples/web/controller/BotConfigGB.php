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
use com\examples\bots\greetingbot\GreetingBot;
use com\tsphpbots\web\core\BaseRESTController;
use com\tsphpbots\user\Auth;
use com\tsphpbots\utils\Log;

/**
 * Page controller for bot GreetingBot
 * NOTE: This controller has no template, it is a pure REST interface.
 * 
 * @created:  22th June 2016
 * @author:   Botorabi
 */
class BotConfigGB extends BaseRESTController {

    /**
     * @var string Log tag
     */
    protected static $TAG = "BotConfigGB";

    /**
     * @var string  Main page, will be used for login if not already done
     */
    protected $renderMainClass  = "Main";

    /**
     * @var array A Summary of bot information
     */
    protected $botSummaryFields = ["id", "botType", "name", "description", "active"];

    /**
     * Return true if the user needs a login for this page.
     * 
     * @implements BaseController
     * 
     * @return boolean      true if login is needed for the page, othwerwise false.
     */
    public function getNeedsLogin() {
        return true;
    }

    /**
     * Allowed access methods (e.g. ["GET", "POST"]).
     * 
     * @implements BaseController
     * 
     * @return string array     Array of access method names.
     */
    public function getAccessMethods() {
        return ["GET", "POST"];
    }

    /**
     * Create the bot model.
     * 
     * @implements BaseRESTController
     * 
     * @param int $botId   Pass a bot ID or 0. Pass 0 in order to create
     *                       a clear model without loading from database.
     * @return Object       The bot model
     */
    protected function createModel($botId = null) {
        return new GreetingBotModel($botId);
    }

    /**
     * Return a list of all available bot IDs in database.
     * 
     * @implements BaseRESTController
     * 
     * @return array  Array of bot IDs in database.
     */
    protected function getAllIDs() {
        return GreetingBot::getAllIDs();
    }

    /**
     * Set the bot default paramerers.
     * 
     * @implements BaseRESTController
     * 
     * @param Object $obj    The object which is created
     * @param array $params  Service call parameters (GET or POST)
     */
    protected function setObjectDefaultParameters($obj, $params) {
        // common parameters
        $obj->setFieldValue("name", $this->getParamString($params, "name", "New Bot"));
        $obj->setFieldValue("description", $this->getParamString($params, "description", ""));
        $obj->setFieldValue("active", 1);

        // bot specific parameters
        $obj->setFieldValue("greetingText", $this->getParamString($params, "greetingText", ""));        
    }

    /**
     * Update the bot parameters.
     * 
     * @param Object $obj    The object which is updated
     * @param array $params  Service call parameters (GET or POST)
     */
    protected function updateObjectParameters($obj, $params) {
        // common bot parameters
        if (isset($params["name"])) {
            $obj->setFieldValue("name", $this->getParamString($params, "name", ""));
        }
        if (isset($params["description"])) {
            $obj->setFieldValue("description", $this->getParamString($params, "description", ""));
        }
        if (isset($params["active"])) {
            $obj->setFieldValue("active", ($this->getParamNummeric($params, "active", 1) === 1) ? 1 : 0);
        }

        // bot specific parameters
        if (isset($params["greetingText"])) {
            $obj->setFieldValue("greetingText", $this->getParamString($params, "greetingText", ""));
        }
    }

    /**
     * Return which data fields should be used for summary displays.
     * Here is a standard compilation, derived classes can return their specific fields.
     * 
     * @overrides BaseRESTController
     * 
     * @return array    Array with data field names used for summary displays.
     */
    protected function getSummaryFields() {
        return $this->botSummaryFields;
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

        // get the combined params
        $params = $this->combineRequestParameters($parameters);

        if (!$this->handleRequest($params)) {
            Log::printEcho($this->createJsonResponse("nok", "Unsupported Request", null));
        }
    }
}