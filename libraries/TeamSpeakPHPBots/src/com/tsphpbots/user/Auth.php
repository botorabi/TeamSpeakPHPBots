<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\user;
use com\tsphpbots\db\DB;
use com\tsphpbots\config\Config;
use com\tsphpbots\utils\Log;
use com\tsphpbots\user\User;

/**
 * Class for authenticating a user and providing login validation.
 * Use only the static methods of this class, no need for instantiation.
 * 
 * @package   com\tsphpbots\user
 * @created   22th June 2016
 * @author    Botorabi
 */
abstract class Auth {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "Auth";

    /**
	 * @var string Session token prefix, all session parameters are stored under this prefix.
	 */
    private static $SESSION_PREFIX          = "tsphpbots";

    /**
	 * @var string Session token for storing the user name
	 */
    private static $SESSIONKEY_USER_NAME    = "userName";

    /**
	 * @var string Session token for storing the user ID
	 */
    private static $SESSIONKEY_USER_ID      = "userID";

    /**
	 * @var string Session token for storing the last user interaction time
	 */
    private static $SESSION_LAST_UPDATE     = "lastUpdate";

    /**
	 * @var int Session recreation time
	 */
    private static $SESSION_SID_UPDATE_TIME = 10 * 60;


    /**
     * Avoid an instance of this class.
     */
    abstract public function __construct();

    /**
     * Is the user logged in?
     * 
     * @return boolean  Return true if the user is logged in, otherwise false.
     */
    static public function isLoggedIn() {

        $loggedin = false;
        if (isset($_SESSION[self::$SESSION_PREFIX]) &&
            isset($_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_NAME])) {
            $loggedin = strlen($_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_NAME]) > 0;
        }

        // check for session timeout and sid refreshing
        if ($loggedin) {
            $currtime   = time();
            $lastupdate = $_SESSION[self::$SESSION_PREFIX][self::$SESSION_LAST_UPDATE];

            if (($currtime - $lastupdate) > (60 * Config::getWebInterface("sessionTimeout"))) {
                unset($_SESSION[self::$SESSION_PREFIX]);
                // note that the session may not have been started (this can happen e.g. during tests)
                if (session_id() != '') {
                    session_destroy();
                }
                //Log::verbose(self::$TAG, "*** session expired");
                return false;
            }
            else if (($currtime - $lastupdate) > self::$SESSION_SID_UPDATE_TIME) {
                session_regenerate_id(true);
                //Log::verbose(self::$TAG, "*** sid was recreated");
            }
            $_SESSION[self::$SESSION_PREFIX][self::$SESSION_LAST_UPDATE] = $currtime;
        }
        
        return $loggedin;
    }

    /**
     * Try to login a user. If the user is already logged in then he/she will be logged out first.
     * 
     * @param string $name    User name
     * @param string $pw      Password is expected in this form:
     *                         md5(the stored pw in database(should also be in md5) + session id as salt)
     * @return boolean        Return true if successfully logged in, otherwise false.
     */
    static public function login($name, $pw) {

        if (self::isLoggedIn()) {
            self::logout();
        }

        if (!DB::connect()) {
            Log::error(self::$TAG, "Login not possible, cannot connect the database!");
            return false;
        }

        $user = User::getUserByLogin($name);
        // first, try user name as login
        if (!$user) {
            // try e-mail as login
            $user = User::getUserByEmail($name);
            if (!$user) {
                Log::info(self::$TAG, "Invalid user login attempt, user does not exist: " . $name);
                return false;
            }
        }

        // validate the password
        if (strcmp($user->getPasswordHash(), $pw)) {
            Log::info(self::$TAG, "Invalid user login attempt, wrong password for user: " . $name);
            return false;
        }

        if (!$user->isActive()) {
            Log::info(self::$TAG, "User login attempt, user is deactivated: " . $name);
            return false;
        }

        $_SESSION[self::$SESSION_PREFIX] = [];
        $_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_NAME]  = $name;
        $_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_ID]    = $user->getObjectID();
        $_SESSION[self::$SESSION_PREFIX][self::$SESSION_LAST_UPDATE]   = time();

        // update the last login time in db
        $res = $user->updateLastLoginTime(time());
        if (!$res) {
            Log::error(self::$TAG, "Could not update user's data (lastLogin)!");
        }

        return true;
    }

    /**
     * Logout the user from the session.
     * 
     * @return boolean  Return true if successfull, otherwise false
     */
    static public function logout() {

        if (!self::isLoggedIn()) {
            return true;
        }
        unset($_SESSION[self::$SESSION_PREFIX]);
        // note that the session may not have been started (this can happen e.g. during tests)
        if (session_id() != '') {
            session_destroy();
        }
        return true;
    }

    /**
     * Time left until automatic logout after long inactivity.
     * 
     * @return int Time left in seconds
     */
    static public function leftTime() {

        $timeleft = 0;
        if (isset($_SESSION[self::$SESSION_PREFIX]) &&
            isset($_SESSION[self::$SESSION_PREFIX][self::$SESSION_LAST_UPDATE])) {
    
            $timeleft = (60 * Config::getWebInterface("sessionTimeout")) -
                        (time() - $_SESSION[self::$SESSION_PREFIX][self::$SESSION_LAST_UPDATE]);
            if ($timeleft < 0) {
                $timeleft = 0;
            }
        }
        return $timeleft;
    }

    /**
     * If the user is logged in then the name of user is returned. Otherwise
     * a null is returned.
     * 
     * @return string  User name, null if not logged in
     */
    static public function getUserName() {
        if (self::isLoggedIn()) {
            return $_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_NAME];
        }
        return null;
    }

    /**
     * If the user is logged in then the user ID is returned. Otherwise
     * a null is returned.
     * 
     * @return int  User ID, null if not logged in
     */
    static public function getUserID() {
        if (self::isLoggedIn()) {
            return $_SESSION[self::$SESSION_PREFIX][self::$SESSIONKEY_USER_ID];
        }
        return null;
    }
}
