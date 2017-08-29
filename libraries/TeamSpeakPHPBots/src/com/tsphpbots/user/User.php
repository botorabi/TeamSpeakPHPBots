<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\user;
use com\tsphpbots\config\Config;
use com\tsphpbots\db\DBObject;
use com\tsphpbots\db\DB;


/**
 * Class containing and maintaining user information.
 * 
 * @package   com\tsphpbots\user
 * @created   26th June 2016
 * @author    Botorabi
 */
class User extends DBObject {

    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "User";

    /**
     *
     * @var string  User table name
     */
    protected static $DB_TABLE_NAME_USER = "user";

    /**
	 * @var string Database table field name: active
	 */
    public static $DB_TABLE_USER_ACTIVE = "active";

    /**
	 * @var string Database table field name: name
	 */
    public static $DB_TABLE_USER_NAME = "name";

    /**
	 * @var string Database table field name: description
	 */
    public static $DB_TABLE_USER_DESC = "description";

    /**
	 * @var string Database table field name: login
	 */
    public static $DB_TABLE_USER_LOGIN = "login";

    /**
	 * @var string Database table field name: email
	 */
    public static $DB_TABLE_USER_EMAIL = "email";

    /**
	 * @var string Database table field name: password
	 */
    public static $DB_TABLE_USER_PW = "password";

    /**
	 * @var string Database table field name: lastLogin
	 */
    public static $DB_TABLE_USER_LAST_LOGIN = "lastLogin";

    /**
	 * @var string Database table field name: roles
	 */
    public static $DB_TABLE_USER_ROLES = "roles";

    /**
	 * @var int Role flag for admin
	 */
    public static $USER_ROLE_ADMIN = 1;

    /**
	 * @var int Role flag for bot master
	 */
    public static $USER_ROLE_BOT_MASTER = 2;


    /**
     * Fields:
     *   name
	 *   active
     *   description
     *   login
     *   email
     *   password
     *   lastLogin
     *   roles
     * 
     * Her are some sample operations provided by base class DBObject.
     * For a full list visit the DBObject class.
     * 
     * 1) Load a User from database: 
     * 
     *     $user = new User($id);
     * 
     *    where $id is the user ID in database.
     * 
     * 2) Accessing the fields:
     * 
     *     echo $user->name;
     *     $user->name = "MyNewName";
     * 
     * 3) Write back the data to database:
     * 
     *     $user->update();
     */


    /**
     * Return the table name.
     * 
     * Base method override.
     * 
     * @return string The database table name
     */
    public static function getTableName() {
        return Config::getDB("tablePrefix") . self::$DB_TABLE_NAME_USER;
    }

    /**
     * Setup the object fields.
     * 
     * @implementes DBObject
     */
    public function setupFields() {
        $this->objectFields[self::$DB_TABLE_USER_ACTIVE]     = 1;
        $this->objectFields[self::$DB_TABLE_USER_NAME]       = "";
        $this->objectFields[self::$DB_TABLE_USER_DESC]       = "";
        $this->objectFields[self::$DB_TABLE_USER_LOGIN]      = "";
        $this->objectFields[self::$DB_TABLE_USER_EMAIL]      = "";
        $this->objectFields[self::$DB_TABLE_USER_PW]         = "";
        $this->objectFields[self::$DB_TABLE_USER_LAST_LOGIN] = 0;
        $this->objectFields[self::$DB_TABLE_USER_ROLES]      = 0;
    }

    /**
     * Get the user roles.
     * 
     * @return int Comnbination of role flags
     */
    public function getRoles() {
        return $this->roles;
    }

    /**
     * Set the user roles using a combination of role flags.
     * 
     * @param int $roleFlags  User roles flags
     */
    public function setRoles($roleFlags) {
        $this->roles = $roleFlags;
    }

    /**
     * Check if the user is active (enabled).
     * 
     * @return  boolean  Return true if the user is active, otherwise false
     */
    public function isActive() {
        return ($this->active != 0);
    }

    /**
     * Get a hashed password which is checked for authentication purpose.
     * 
     * @return string  Password hash
     */
    public function getPasswordHash() {
        return md5($this->password . htmlspecialchars(session_id()));
    }

    /**
     * Try to find a user with given login name.
     * 
     * @param string $login User login
     * @return User         User object if found, or null if no user found with given login.
     */
    public static function getUserByLogin($login) {   
        $users = DB::getObjects(self::getTableName(), [self::$DB_TABLE_USER_LOGIN => $login]);
        if (count($users) > 1) {
            Log::error(self::$TAG, "Internal error, more than one user entry was found in database with same login!");
            return null;
        }
        if (count($users) === 1) {
            $user = new User();
            $user->objectFields = $users[0];
            return $user;
        }
        return null;
    }

    /**
     * Try to find a user with given email.
     * 
     * @param string $email User's email
     * @return User         User object if found, or null if no user found with given email.
     */
    public static function getUserByEmail($email) {   
        $users = DB::getObjects(self::getTableName(), [self::$DB_TABLE_USER_EMAIL => $email]);
        if (count($users) > 1) {
            Log::error(self::$TAG, "Internal error, more than one user entry was found in database with same email!");
            return null;
        }
        if (count($users) === 1) {
            $user = new User();
            $user->objectFields = $users[0];
            return $user;
        }
        return null;
    }

    /**
     * Update the last login time in database.
     * 
     * @param int $time   Updated time
     * @return boolean    Return true if successful, otherwise false
     */
    public function updateLastLoginTime($time) {
        $this->lastLogin = $time;
        return $this->update([self::$DB_TABLE_USER_LAST_LOGIN => $time]);
    }
}
