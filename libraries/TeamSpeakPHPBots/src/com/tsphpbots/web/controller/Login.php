<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\controller;
use com\tsphpbots\web\core\BaseController;
use com\tsphpbots\user\Auth;

/**
 * Login Page controller
 * 
 * @package   com\tsphpbots\web\controller
 * @created   22th June 2016
 * @author    Botorabi
 */
class Login extends BaseController {

    /**
	 * @var string Page controller name. This controller has no own template and redirects to Main page.
	 */
    protected $renderMainClass = "Main";

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
     * Create the view for the web page.
     * 
     * @param $parameters  URL parameters such as GET or POST
     */
    public function view($parameters) {

        if (Auth::isLoggedIn()) {
            $this->redirectView($this->renderMainClass);
            return;
        }

        // check if this is a login attempt
        if (isset($parameters["POST"])) {

            $post = $parameters["POST"];
            if (isset($post["un"]) && isset($post["pw"])) {

                $un = $post["un"];
                $pw = $post["pw"];

                if (Auth::login($un, $pw)) {
                    $this->redirectView($this->renderMainClass);
                    return;
                }
            }
        }

        $this->redirectView($this->renderMainClass, ["login" => ""]);
    }
}