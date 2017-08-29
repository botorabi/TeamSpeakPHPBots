<?php
/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\utils;
use com\tsphpbots\config\Config;
use com\tsphpbots\db\DB;

/**
 * Utilities for testing
 * 
 * @package   com\tsphpbots\utils
 * @created   22th June 2016
 * @author    Botorabi
 */
class TestUtils  {

    /**
     * Get the user table name.
     * 
     * @return string   Name of table for user
     */
    public static function getTableNameUser() {
        return Config::getDB("tablePrefix") . 'user';
    }

    /**
     * Create a new user entry in database.
     * 
     * @param string $name         User name
     * @param string $login        Login
     * @param string $password     Password
     * @param string $email        Email
     * @return int                 User ID
     */
    public static function createUser($name, $login, $password, $email = null) {

        $fields = [
            "name"      => $name,
            "login"     => $login,
            "password"  => md5($password),
            "email"     => $email,
            "roles"     => 1
        ];
        return DB::createObject(self::getTableNameUser(), $fields);
    }
}
