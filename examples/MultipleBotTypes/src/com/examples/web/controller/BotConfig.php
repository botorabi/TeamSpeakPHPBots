<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\web\controller;
use com\tsphpbots\web\controller\BaseController;
use com\tsphpbots\user\Auth;

/**
 * Page controller for bot configuration
 * 
 * First Created:  4th August 2016
 * Author:         boto
 */
class BotConfig extends BaseController {

    /**
     * @var string Log tag
     */
    protected static $TAG = "BotConfig";

    /**
     * @var string Class name used for automatically find the proper template
     */
    public $renderClassName = "BotConfig";

    /**
     * @var string  Main page, will be used for login if not already done
     */
    protected $renderMainClass = "Main";

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
        $this->renderView($this->renderClassName, null);
    }
}