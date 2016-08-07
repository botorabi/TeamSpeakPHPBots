<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\controller;
use com\tsphpbots\web\core\BaseController;
use com\tsphpbots\user\User;
use com\tsphpbots\user\Auth;
use com\tsphpbots\utils\Log;

/**
 * UserAdmin Page controller
 * 
 * This controller provides a REST interface for user administration such as
 * create, update, delete, listing, and user details.
 *
 * @package   com\tsphpbots\web\controller
 * @created   3rd July 2016
 * @author    Botorabi
 */
class UserAdmin extends BaseController {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "UserAdmin";

    /**
     *
     * @var string  Class name used for automatically find the proper template
     */
    protected $renderClassName = "";

    /**
     *
     * @var string  Main page's controller name
     */
    protected $renderMainClass = "Main";

    /**
     *
     * @var array Fields used for showing a summary of users
     */
    protected $userSummaryFields = ["id", "name", "login", "description", "active", "lastLogin", "roles"];
    
    /**
     * @var User Currently logged in user
     */
    protected $loggedInUser = null;

    /**
	 * @var int OP flag for deletion
	 */
    protected static $OP_DELETE = 1;

    /**
	 * @var int OP flag for updating
	 */
    protected static $OP_UPDATE = 2;

    /**
	 * @var int OP flag for activation
	 */
    protected static $OP_ACTIVATE = 4;

    /**
	 * @var int OP flag for changing  the role
	 */
    protected static $OP_MODIFY_ROLES = 8;

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
     * @return array     Array of access method names.
     */
    public function getAccessMethods() {
        return ["GET", "POST"];
    }

    /**
     * Set the render class name. This can be used by a derived class which
     * can provide an HTML template in addition to the provided REST functions in 
     * this class.
     * 
     * @param string $name
     */
    public function setRenderClassName($name) {
        $this->renderClassName = $name;
    }

    /**
     * Create a view for user administration.
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

        $this->loggedInUser = new User(Auth::getUserID());
        if (is_null($this->loggedInUser)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "internal error, user not found!"]));
            return;
        }

        // operations which are preserved to admin
        if ($this->loggedInUser->getRoles() & User::$USER_ROLE_ADMIN) {
            if (isset($params["list"])) {
                $this->cmdUserList();
            }
            else if (isset($params["id"])) {
                $this->cmdUserDetails($params["id"]);
            }
            else if (isset($params["create"])) {
                $this->cmdCreateUser($params);
            }
            else if (isset($params["update"])) {
                $this->cmdUpdateUser($params);
            }
            else if (isset($params["delete"])) {
                $this->cmdDeleteUser($params);
            }
            else {
                $this->defaultResponse();
            }
        }
        else { // other user roles go here

            if (isset($params["list"])) {
                $this->cmdUserList($this->loggedInUser->getObjectID());
            }
            else if (isset($params["id"])) {
                 $this->cmdUserDetails($this->loggedInUser->getObjectID());
            }
            else if (isset($params["update"])) {
                $this->cmdUpdateUser($params);
            }
            else if (isset($params["delete"]) || isset($params["create"])) {
                Log::printEcho(json_encode(["result" => "nok", "reason" => "no permission"]));
            }
            else {
                $this->defaultResponse();
            }
        }
    }

    /**
     * If a render class is set then render it now, otherwise repond with a nok.
     */
    protected function defaultResponse() {
        // this class may be set by a derived class providing an own template
        if (strlen($this->renderClassName) > 0) {
            $this->renderView($this->renderClassName, null);
        }
        else {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid request cmd"]));                    
        }
    }

    /**
     * Create a JSON response for a user summary.
     * 
     * @param int $userID   If not null then the summary will contain only the given user,
     *                      otherwise all users are listed in the summary.
     * @return boolean      Return true if successful, otherweise false
     */
    protected function cmdUserList($userID = null) {

        $response = [];
        if (is_null($userID)) {
            $user = new User();
            $userids = $user->getAllObjectIDs();
            if (is_null($userids)) {
                Log::error(self::$TAG, "Could not access the database!");
                Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
                return false;
            }
        }
        else {
            $userids = [$userID];
        }

        $admin = ($this->loggedInUser->roles & User::$USER_ROLE_ADMIN) === 0 ? false : true;
        
        foreach($userids as $id) {
            $user = new User($id);
            if (is_null($user)) {
                Log::warning(self::$TAG, "Could not access the database!");
                Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
                return false;
            }
            $record = [];
            foreach($user->getFields() as $field => $value) {
                if (!in_array($field, $this->userSummaryFields)) {
                    continue;
                }
                $record[$field] = $value;
            }

            // set the allowed operations
            if ($id !== $this->loggedInUser->getObjectID()) {
                $ops = $admin ? (self::$OP_DELETE | self::$OP_UPDATE | self::$OP_ACTIVATE | self::$OP_MODIFY_ROLES) : 0;
            }
            else {
                $ops = self::$OP_UPDATE;
            }

            $record["ops"] = $ops;
            $response[] = $record;
        }
        $json = json_encode(["result" => "ok", "allowcreation" => $admin ? "1" : "0", "data" => $response]);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a JSON response for an user given its ID.
     * 
     * @param int $id     User ID
     * @return boolean    Return true if successful, otherweise false
     */
    protected function cmdUserDetails($id) {
        
        $user = new User($id);
        if (is_null($user->getObjectID())) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
            return false;
        }

        $response = [];
        foreach($user->getFields() as $field => $value) {
            // don't send the password
            if (strcmp($field, User::$DB_TABLE_USER_PW) === 0) {
                continue;
            }
            $response[$field] = $value;
        }
        $json = json_encode(["result" => "ok", "data" => $response]);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a new user.
     * 
     * @param array $params       Page parameters containing the bot configuration
     */
    protected function cmdCreateUser($params) {

        $user = new User();

        // validate the login and email
        if (!isset($params["login"]) || (strlen($params["login"]) < 4)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input: Login", "data" => "login"]));
            return;
        }
        $checklogin = User::getUserByLogin($this->getParamString($params, "login", ""));
        if (!is_null($checklogin)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "non-unique input: Login", "data" => "login"]));
            return;            
        }

        if (!isset($params["email"]) || (strlen($params["email"]) === 0)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input: E-Mail", "data" => "email"]));
            return;
        }
        $checkemail = User::getUserByEmail($this->getParamString($params, "email", ""));
        if (!is_null($checkemail)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "non-unique input: E-Mail", "data" => "email"]));
            return;            
        }

        // validate name and password
        if (!isset($params["name"]) || (strlen($params["name"]) === 0)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input: Name", "data" => "name"]));
            return;
        }
        if (!isset($params["password"]) || (strlen($params["password"]) < 16)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input: Password", "data" => "password"]));
            return;
        }

        $user->setFieldValue("lastLogin", 0);
        $user->setFieldValue("active", 1);
        $user->setFieldValue("name", $this->getParamString($params, "name", ""));
        $user->setFieldValue("description", $this->getParamString($params, "description", ""));
        $user->setFieldValue("login", $this->getParamString($params, "login", ""));
        $user->setFieldValue("email", $this->getParamString($params, "email", ""));
        $user->setFieldValue("password", $this->getParamString($params, "password", ""));

        $roles = $this->getParamNummeric($params, "roles", User::$USER_ROLE_BOT_MASTER);
        $roles &= (User::$USER_ROLE_ADMIN | User::$USER_ROLE_BOT_MASTER);
        $user->setFieldValue("roles", $roles);

        $id = $user->create();

        if (is_null($id)) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
        }
        else {
            $json = json_encode(["result" => "ok", "data" => ["id" => $user->getObjectID()]]);
            Log::printEcho($json);
        }
    }

    /**
     * Update an user. The user ID must be given in parameter field "update".
     * 
     * @param array $params      Page parameters, expected user ID must be in field "update".
     */
    protected function cmdUpdateUser($params) {

        if (!isset($params["update"])) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input"]));
            return;
        }

        $admin = false;
        // an admin can modify every user's data, a non-admin can update only own data
        if ($this->loggedInUser->getRoles() & User::$USER_ROLE_ADMIN) {
            $userid = $params["update"];
            $admin = true;
        }
        else {
            $userid = $this->loggedInUser->getObjectID();
        }
        $user = new User($userid);
        if ($user->getObjectID() === 0) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid ID"]));
            return;
        }

        //! NOTE login and email cannot be updated once the user was created!

        if (isset($params["name"])) {
            $user->setFieldValue("name", $this->getParamString($params, "name", ""));
        }
        if (isset($params["description"])) {
            $user->setFieldValue("description", $this->getParamString($params, "description", ""));
        }
        
        // updates preserved to admins only
        if ($admin) {
            if (isset($params["active"])) {
                // only admins are allowed to change the active flag of other users
                if ($this->loggedInUser->getObjectID() === $userid) {
                    Log::printEcho(json_encode(["result" => "nok", "reason" => "cannot deactivate yourself"]));
                    return;                
                }
                else {
                    $user->setFieldValue("active", ($this->getParamNummeric($params, "active", 1) === 1) ? 1 : 0);
                }
            }
            if (isset($params["roles"])) {
                $roles = $this->getParamNummeric($params, "roles", User::$USER_ROLE_BOT_MASTER);
                // changing own roles? then make sure that the admin keeps being an admin!
                if ($this->loggedInUser->getObjectID() === $userid) {
                    $roles |= User::$USER_ROLE_ADMIN;
                }
                else {
                    $roles &= (User::$USER_ROLE_ADMIN | User::$USER_ROLE_BOT_MASTER);
                }
                $user->setFieldValue("roles", $roles);
            }
        }

        // passwords must be at least 16 chars
        if (isset($params["password"]) && isset($params["passwordOld"])) {

            if ((strlen($params["password"]) > 15) && (strlen($params["passwordOld"]) > 15)) {
                if (strcmp($this->loggedInUser->getPasswordHash(), $params["passwordOld"]) !== 0) {
                    Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid password"]));
                    return;
                }
                $user->setFieldValue("password", $params["password"]);
            }
            else {
                Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid password, must be at least 8 characters."]));
                return;
            }
        }

        if ($user->update() === false) {
             Log::printEcho(json_encode(["result" => "nok", "reason" => "db"]));
        }
        else {
            $json = json_encode(["result" => "ok", "data" => ["id" => $user->getObjectID()]]);
            Log::printEcho($json);
        }
    }

    /**
     * Delete an user. The user ID must be given in parameter field "delete".
     * 
     * @param array $params      Page parameters, expected user ID must be in field "delete".
     */
    protected function cmdDeleteUser($params) {

        if (!isset($params["delete"])) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid input"]));
            return;
        }

        $id = $params["delete"];
        if ($this->loggedInUser->getObjectID() === $id) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "cannot delete yourself"]));
            return;                
        }

        $user = new User($id);
        if ($user->delete() === false) {
            Log::printEcho(json_encode(["result" => "nok", "reason" => "invalid ID"]));
        }
        else {
            Log::printEcho(json_encode(["result" => "ok", "data" => ["id" => $id]]));
        }
    }
}