<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\controller;
use com\tsphpbots\web\controller\BaseController;
use com\tsphpbots\user\Auth;


/**
 * Web interface's main page controller
 * 
 * @package   com\tsphpbots\web\controller
 * @created   22th June 2016
 * @author    Botorabi
 */
class Main extends BaseController {

    /**
	 * @var string Page controller name
	 */
    public $renderClassName = "Main";

    /**
     * Return true if the user needs a login for this page.
     * 
     * @return boolean      true if login is needed for the page, othwerwise false.
     */
    public function getNeedsLogin() {
        return false;
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
     * Create the view for the web page. A new page property is added by this
     * controller: 'loginDlg'. It can have the value 'on' or ''.
     * If the get parameter 'login' exists, then 'loginDlg' is set to 'on'.
     * It can be used for popping up a login dialog in HTML code.
     * 
     * @param $parameters  URL parameters such as GET or POST
     */
    public function view($parameters) {

        // check if the login dialog should be displayed on page loading
        $props = ["loginDlg" => ""];
        if (!Auth::isLoggedIn()) {
            if (isset($parameters["GET"]) && isset($parameters["GET"]["login"])) {
                $props["loginDlg"] = "on";
            }
        }
        $this->renderView($this->renderClassName, $props);
    }
}