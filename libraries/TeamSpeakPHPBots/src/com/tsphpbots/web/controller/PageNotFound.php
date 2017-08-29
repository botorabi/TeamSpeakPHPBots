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


/**
 * This controller is called whenever a controller (page) was not found.
 * 
 * @package   com\tsphpbots\web\controller
 * @created   29th June 2016
 * @author    Botorabi
 */
class PageNotFound extends BaseController {

    /**
     *
     * @var string  Class name used for automatically find the proper template
     */
    public $renderClassName = "PageNotFound"; // This page has no own html file!

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
        $this->renderView($this->renderClassName, null);
    }
}