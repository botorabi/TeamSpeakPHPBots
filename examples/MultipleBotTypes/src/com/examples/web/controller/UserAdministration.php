<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\examples\web\controller;
use com\tsphpbots\web\controller\UserAdmin;

/**
 * UserAdministration Page controller
 * 
 * This controller provides a REST interface for user administration such as
 * create, update, delete, listing, and user details. In addition it supports
 * an HTML page template for managing users.
 *
 * @created:  3rd July 2016
 * @author:   Botorabi
 */
class UserAdministration extends UserAdmin {

    protected static $TAG = "UserAdministration";

    /**
     * Create a view for user administration.
     * 
     * @param $parameters  URL parameters such as GET or POST
     */
    public function view($parameters) {
        $this->setRenderClassName("UserAdministration");
        parent::view($parameters);
    }
}