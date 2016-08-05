<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\db;
use com\tsphpbots\utils\Log;
use com\tsphpbots\db\DB;

/**
 * Base class of all databank objects. This class implements the basic
 * functionality for accessing the object data in a databank.
 * 
 * @package   com\tsphpbots\db
 * @created   26th June 2016
 * @author    Botorabi
 */
abstract class DBObject {
   
    /**
     * @var string Class tag for logging
     */
    protected static $TAG = "DBObject";

    /**
     * @var array Object fields
     */
    protected $objectFields = [];

    /**
     * Constructor with optional object ID. If an ID is given then the object 
     * will be loaded from database.
     * 
     * @param int $id  Object ID (e.g. the result of 'create').
     */
    public function __construct($id = null) {
        $this->setupFields();
        if (!is_null($id) && ($id !== 0)) {
            $this->loadObject($id);
        }
    }

    /**
     * Return the table name. This must be implemented by derived class.
     * 
     * @return string   Database table name
     */
    abstract static public function getTableName();

    /**
     * Implement this method by derived classes in order to define the object
     * fields. The impelementation should fill the $objectFields array with
     * field names and their initial values.
     */
    abstract public function setupFields();

    /**
     * Get the object fields.
     * 
     * @return array Object fields
     */
    public function getFields() {
        return $this->objectFields;
    }

    /**
     * Get the field value given its name.
     * 
     * @param string $name     Field name
     * @return Object          Fields value, or null if the field does not exist.
     */
    public function getFieldValue($name) {
        return $this->__get($name);
    }

    /**
     * Set the field value given its name.
     * 
     * @param string $name     Field name
     * @param Object $value    Field value
     */
    public function setFieldValue($name, $value) {
        $this->__set($name, $value);
    }

    /**
     * Magic override for getting a field value.
     * Example for a field name called 'myemail': echo $obj->myemail;
     * 
     * @param string $name   Field name
     * @return Object        Field value, or null if the field does not exist.
     */
    public function __get($name) {
        if (array_key_exists($name, $this->objectFields)) {
            return $this->objectFields[$name];
        }
        else {
            Log::error(self::$TAG, "DBObject (" . $this->getTableName() . "): Get-Field '" . $name . "' does not exist!");
            return null;
        }    
    }

    /**
     * Magic override for setting a field value.
     * Example for a field name called 'myemail': $obj->myemail = "MyEmail";
     * 
     * @param string $name     Field name
     * @param object $value    Field value
     */
    public function __set($name, $value) {
        if (array_key_exists($name, $this->objectFields)) {
            $this->objectFields[$name] = $value;
        }
        else {
            Log::error(self::$TAG, "DBObject (" . $this->getTableName() . "): Set-Field '" . $name . "' does not exist!");
        }
    }

    /**
     * Return the object ID, if it does not exist then return null.
     * 
     * @return int  Object ID or null if the object was not loaded from database previously.
     */
    public function getObjectID() {
        if (array_key_exists("id", $this->objectFields) && isset($this->objectFields["id"])) {
            return $this->objectFields["id"];
        }
        return null;
    }

    /**
     * Get all object IDs found in database.
     * 
     * @return array   All found objects IDs (can also be empty), or null if the table
     *                 does not exist or there is no connection to database.
     */
    public function getAllObjectIDs() {
        return DB::getObjectIDs($this->getTableName());
    }

    /**
     * Retrieve the object fields from database given its ID. If successful then
     * the object fields are store and can be accessed by method 'getFields' or by
     * their names as follows: $this->fieldname
     * 
     * @param int $id       Object ID
     * @return boolean      Return true if an object with given ID could be loaded, otherwise false.
     */
    public function loadObject($id) {
        $objects = DB::getObjects($this->getTableName(), ["id" => $id]);
        if (count($objects) != 1) {
            $this->objectFields = [];
            return false;
        }
        $this->objectFields = $objects[0];
        return true;
    }

    /**
     * Create a new object with given field values overwriting the
     * current fields.
     *
     * @param array $fields Optional field values, an array of tuples [field name -> value].
     * @return int          Object ID if successful, otherwise null.
     */
    public function create(array $fields = null) {
        if (is_null($fields)) {
            $fields = $this->objectFields;
        }
        else {
            // make sure that the fields are really valid object fields!
            foreach($fields as $field => $value) {
                $this->setFieldValue($field, $value);
            }
        }

        $id = DB::createObject($this->getTableName(), $fields);
        if (is_null($id)) {
            return null;
        }
        if ($this->loadObject($id)) {
            return $id;
        }
        return null;
    }

    /**
     * Delete the data object from the databank.
     * 
     * @return boolean  Return true if successfully, otherwise false
     */
    public function delete() {
        $res = DB::deleteObject($this->getTableName(), $this->id);
        if ($res === true) {
            $this->id = 0;
        }
        return $res;
    }

    /**
     * Store back the object changed to databank. Use $fields for updating only given
     * fields, otherwise all fields are updated.
     *
     * @param array $fields  Optional field values which are updated, an array of 
     *                       tuples [field name -> value]. If this is null then
     *                       all object fields are updated.
     * @return boolean       true if successful, otherwise false
     */
    public function update(array $fields = null) {

        if ($fields === null) {
            $fields = $this->objectFields;
        }
        if ((count($fields) === 0) || ($this->getObjectID() === null)) {
            Log::warning(self::$TAG, "Cannot update the database object, invalid table info!");
            return false;
        }
        return DB::updateObject($this->getTableName(), $this->getObjectID(), $fields);
    }

    /**
     * Given a string containing comma separated numbers return an array of
     * numbers. This utility method is used for convenience in order to store
     * number arrays as a string.
     * 
     * @param string $str       String containing comma separated numbers
     * @return array            Array of numbers
     */
    public function stringToNumArray($str) {
        $nums = [];
        if (strlen($str) > 0) {
            $elems = explode(",", $str);
            foreach($elems as $elem) {
                if (is_numeric($elem)) {
                   $nums[] = (int)trim($elem); 
                }
            }
        }
        return $nums;
    }
    /**
     * Given an array of numbers return a string containing the numbers
     * by comma separation.
     * 
     * @param array $numbers        Array of numbers
     * @return string               String containing comma separated numbers.
     */
    public function numArrayToString(array $numbers) {
        $str = "";
        foreach($numbers as $num) {
            if (strlen($str) > 0) {
                $str .=",";
            }
            $str .= $num;
        }
        return $str;
    }
}
