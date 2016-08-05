<?php
/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */

namespace com\tsphpbots\web\core;
use com\tsphpbots\web\core\BaseController;
use com\tsphpbots\utils\Log;

/**
 * Base controller for all kinds of REST interfaces which allow data object manipulation.
 * It provides an interface for following operations:
 * 
 *  list, create, update, delete, details
 * 
 * NOTE: This controller has no template, it is a pure REST interface.
 * 
 * @package   com\tsphpbots\web\core
 * @created:  5th August 2016
 * @author:   Botorabi
 */
abstract class BaseRESTController extends BaseController {

    /**
     * @var string Log tag
     */
    protected static $TAG = "BaseRESTController";

    /**
     * Create the model. It must be a DBObject compatible type.
     * 
     * @param int $objId   Pass an object ID or 0. Pass 0 in order to create
     *                       a clear model without loading from database.
     * @return Object       The model, an instance of a class inheriting from DBObject.
     */
    abstract protected function createModel($objId = 0);

    /**
     * Return a list of all available IDs in database.
     * 
     * @return array  Array of IDs in database.
     */
    abstract protected function getAllIDs();

    /**
     * Set the parameters in the object considering the $params. This is called e.g. when
     * a new object is being created. This method should define also default values for parameters
     * which are not given in $params.
     * 
     * @param Object $obj    The object which is created
     * @param array $params  Service call parameters (GET or POST)
     */
    abstract protected function setObjectDefaultParameters($obj, $params);

    /**
     * Update the given parameters in $params in the database. This is called whenever
     * a data update request arrives. In contrast to setObjectDefaultParameters this methos updates
     * only the fields given in $params.
     * 
     * @param Object $obj    The object which is updated
     * @param array $params  Service call parameters (GET or POST)
     */
    abstract protected function updateObjectParameters($obj, $params);

    /**
     * Return which data fields should be used for summary display.
     * Here is a standard compilation, derived classes can return their specific fields.
     * 
     * @return array    Array with data field names used for summary displays.
     */
    protected function getSummaryFields() {
        return ["id", "name", "description", "active"];
    }

    /**
     * Handle an incoming request.
     * 
     * @param array $params  URL parameters such as GET or POST
     */
    public function handleRequest($params) {

        if (isset($params["list"])) {
            $this->cmdList();
        }
        else if (isset($params["id"])) {
            $this->cmdDetails($params["id"]);
        }
        else if (isset($params["create"])) {
            $this->cmdCreate($params);
        }
        else if (isset($params["update"])) {
            $this->cmdUpdate($params);
        }
        else if (isset($params["delete"])) {
            $this->cmdDelete($params);
        }
        else {
            return false;
        }
        
        return true;
    }

    /**
     * Assemble the data fields into an array.
     * 
     * @param Object  $obj      The object
     * @param boolean $summary  Pass true in order to assemble only the summary fields
     * @return array            Object data fields
     */
    protected function getDataFields($obj, $summary = true) {
        $response = [];
        foreach($obj->getFields() as $field => $value) {
            if ($summary && !in_array($field, $this->getSummaryFields())) {
                continue;
            }
            $response[$field] = $value;
        }
        return $response;
    }

    /**
     * Create a JSON formatted string out of given fields. Any of the fields may
     * be null or empty.
     * 
     * @param string $result        Result string
     * @param string $reason        Reason string
     * @param mixed $data           Data, it can be also an array
     * @return string               JSON formatted string for the given inputs.
     */
    protected function createJsonResponse($result, $reason, $data) {
        $res = [];
        if (!is_null($result) || (strlen($result) > 0)) {
            $res["result"] = $result;
        }

        if (!is_null($reason) || (strlen($reason) > 0)) {
            $res["reason"] = $reason;
        }

        if (!is_null($data) || (strlen($data) > 0)) {
            $res["data"] = $data;
        }        
        return json_encode($res);
    }

    /**
     * Create a JSON response for all available objects.
     * 
     * @return boolean true if successful, otherweise false
     */
    protected function cmdList() {
        $data = [];
        $ids = $this->getAllIDs();
        if (is_null($ids)) {
            Log::error(self::$TAG, "Could not access the database!");
            Log::printEcho($this->createJsonResponse("nok", "Database Access", null));
            return false;
        }

        foreach($ids as $id) {
            $obj = $this->createModel($id);
            if (is_null($obj)) {
                Log::warning(self::$TAG, "Could not access the database!");
                Log::printEcho($this->createJsonResponse("nok", "Database Access", null));
                return false;
            }
            $record = $this->getDataFields($obj, true);
            $data[] = $record;
        }
        $json = $this->createJsonResponse("ok", null, $data);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a JSON response for a configuration given its ID.
     * 
     * @param int $objId  Object ID
     * @return boolean    true if successful, otherweise false
     */
    protected function cmdDetails($objId) {
        $obj = $this->createModel($objId);
        if (is_null($obj->getObjectID())) {
            Log::printEcho($this->createJsonResponse("nok", "Database Access", null));
            return false;
        }

        $data = $this->getDataFields($obj, false);
        $json = $this->createJsonResponse("ok", null, $data);
        Log::printEcho($json);
        return true;
    }

    /**
     * Create a new object.
     * 
     * @param array $params   Request parameters containing the object configuration
     */
    protected function cmdCreate($params) {

        $obj = $this->createModel();
        $this->setObjectDefaultParameters($obj, $params);
        $id = $obj->create();
        
        if (is_null($id)) {
            Log::printEcho($this->createJsonResponse("nok", "Database Access", null));
        }
        else {
            $data = $this->getDataFields($obj, false);
            $json = $this->createJsonResponse("ok", null, $data);
            Log::printEcho($json);
        }
    }

    /**
     * Update an object. The ID must be given in parameter field "update".
     * 
     * @param array $params      Request parameters, expected ID must be in field "update".
     */
    protected function cmdUpdate($params) {

        if (!isset($params["update"])) {
            Log::printEcho($this->createJsonResponse("nok", "Invalid Input", null));
            return;
        }

        $objid = $params["update"];
        $obj = $this->createModel($objid);
        if ($obj->getObjectID() === 0) {
            Log::printEcho($this->createJsonResponse("nok", "Invalid ID", null));
            return;
        }

        // update the object specific params, this is provided by the derived class
        $this->updateObjectParameters($obj, $params);

        if ($obj->update() === false) {
             Log::printEcho($this->createJsonResponse("nok", "Database Access", null));
        }
        else {
            $data = $this->getDataFields($obj, false);
            $json = $this->createJsonResponse("ok", null, $data);
            Log::printEcho($json);
        }
    }

    /**
     * Delete an object. The ID must be given in parameter field "delete".
     * 
     * @param array $params  Request parameters, expected ID must be in field "delete".
     */
    protected function cmdDelete($params) {

        if (!isset($params["delete"])) {
            Log::printEcho($this->createJsonResponse("nok", "Invalid Input", null));
            return;
        }

        $objid = $params["delete"];
        $obj = $this->createModel($objid);
        if ($obj->delete() === false) {
            Log::printEcho($this->createJsonResponse("nok", "Invalid ID", null));
        }
        else {
            $json = $this->createJsonResponse("ok", null, ["id" => $objid]);
            Log::printEcho($json);
        }
    }
}