/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */
/* 
 * Created on : 29th July, 2016
 * Author     : Botorabi (boto)
 */

var REST = REST || {};

function initREST(logger) {

    REST.logger = logger;

    /**
     * Asynchronous request which uses POST and expects JSON as response.
     * 
     * @param requestUrl        Request URL
     * @param requestData       Request data (e.g. form data)
     * @param responseCallback  Callback for reponse notification.
     *                          The response is parsed as JSON and a corresponding
     *                          java object is used for the callback.
     */
    function requestJSON(requestUrl, requestData, responseCallback) {
        $.ajax({
            type: "POST",
            url: requestUrl,
            data: requestData,
            success: function(data) {
                if (responseCallback !== null) {
                    var res = null;
                    try {
                        res = $.parseJSON(data);
                    }
                    catch(e) {
                        REST.logger.e("Exception occurred while parsing JSON response: " + e + " data: '" + data + "'");
                    }
                    responseCallback(res);
                }
             }
        });
    }
    
    /**
     * Get the time left before automatic logout.
     * 
     * @param callback  Callback function used when the results are ready.
     */
    REST.getTimeLeft = function(callback) {
        requestJSON("?page=timeLeft", null, callback);
    };

    /**
     * Get a list of bots.
     * 
     * @param ctrlName  Bot controller name
     * @param callback  Callback function used when the results are ready.
     */
    REST.getBotList = function(ctrlName, callback) {
        requestJSON("?page=" + ctrlName + "&list=0", null, callback);
    };

    /**
     * Get the bot with given ID.
     * 
     * @param ctrlName  Bot controller name
     * @param id        Bot ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.getBot = function(ctrlName, id, callback) {
        requestJSON("?page=" + ctrlName + "&id=" + id, null, callback);
    };

    /**
     * Create a new bot with specified parameters in given formular.
     * 
     * @param ctrlName  Bot controller name
     * @param formID    Forumular ID, the formular contains the bot data.
     * @param callback  Callback function used when the results are ready.
     */
    REST.createBot = function(ctrlName, formID, callback) {
        var formdata = $("#" + formID).serializeArray();
        formdata.push({name: "create", value : "0"});
        requestJSON("?page=" + ctrlName, formdata, callback);
    };

    /**
     * Update a bot with specified parameters in given formular.
     * 
     * @param ctrlName  Bot controller name
     * @param id        Bot ID
     * @param formID    Forumular ID, the formular contains the bot data.
     * @param callback  Callback function used when the results are ready.
     */
    REST.updateBot = function(ctrlName, id, formID, callback) {
        var formdata = $("#" + formID).serializeArray();
        formdata.push({name: "update", value : id});
        requestJSON("?page=" + ctrlName, formdata, callback);
    };

    /**
     * Enable/disable a bot with given ID.
     * 
     * @param ctrlName  Bot controller name
     * @param id        Bot ID
     * @param enable    true for enabling the bot, false for disabling it.
     * @param callback  Callback function used when the results are ready.
     */
    REST.enableBot = function(ctrlName, id, enable, callback) {
        var reqdata = [];
        reqdata.push({name: "update", value : id});
        reqdata.push({name: "active", value: enable ? "1" : "0"});
        requestJSON("?page=" + ctrlName, reqdata, callback);
    };

    /**
     * Delete the bot with given ID.
     * 
     * @param ctrlName  Bot controller name
     * @param id        Bot ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.deleteBot = function(ctrlName, id, callback) {
        requestJSON("?page=" + ctrlName + "&delete=" + id, null, callback);
    };

    /**
     * Get a list of users.
     * 
     * @param callback  Callback function used when the results are ready.
     */
    REST.getUserList = function(callback) {
        requestJSON("?page=UserAdmin&list=0", null, callback);
    };

    /**
     * Get the user with given ID.
     * 
     * @param id        User ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.getUser = function(id, callback) {
        requestJSON("?page=UserAdmin&id=" + id, null, callback);
    };

    /**
     * Create a new user with specified parameters.
     * 
     * @param fields    Array containing the user data.
     * @param callback  Callback function used when the results are ready.
     */
    REST.createUser = function(fields, callback) {
        var formdata = fields;
        formdata.push({name: "create", value : "0"});
        requestJSON("?page=UserAdmin", formdata, callback);
    };

    /**
     * Update an user with specified parameters in given formular.
     * 
     * @param id        User ID
     * @param fields    Array containing the user data.
     * @param callback  Callback function used when the results are ready.
     */
    REST.updateUser = function(id, fields, callback) {
        var formdata = fields;
        formdata.push({name: "update", value : id});
        requestJSON("?page=UserAdmin", formdata, callback);
    };

    /**
     * Enable/disable an user with given ID.
     * 
     * @param id        user ID
     * @param enable    true for enabling the bot, false for disabling it.
     * @param callback  Callback function used when the results are ready.
     */
    REST.enableUser = function(id, enable, callback) {
        var reqdata = [];
        reqdata.push({name: "update", value : id});
        reqdata.push({name: "active", value: enable ? "1" : "0"});
        requestJSON("?page=UserAdmin", reqdata, callback);
    };

     /**
     * Set the user role.
     * 
     * @param id        user ID
     * @param roleFlag  One of role flags.
     * @param callback  Callback function used when the results are ready.
     */
    REST.setUserRole = function(id, roleFlag, callback) {
        var reqdata = [];
        reqdata.push({name: "update", value : id});
        reqdata.push({name: "roles", value: roleFlag});
        requestJSON("?page=UserAdmin", reqdata, callback);
    };

    /**
     * Delete the user with given ID.
     * 
     * @param id        User ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.deleteUser = function(id, callback) {
        requestJSON("?page=UserAdmin&delete=" + id, null, callback);        
    };

    /**
     * Request for bot server status.
     * 
     * @param callback  Callback function used when the results are ready.
     */
    REST.getBotServiceStatus = function(callback) {
        requestJSON("?page=BotServer&status=", null, callback);        
    };

    /**
     * Request for starting the bot server.
     * 
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceStart = function(callback) {
        requestJSON("?page=BotServer&start=", null, callback);        
    };

    /**
     * Request for stopping the bot server.
     * 
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceStop = function(callback) {
        requestJSON("?page=BotServer&stop=", null, callback);        
    };

    /**
     * Request for adding a bot with given type and id. This is used in order to let changes
     * in a bot take place without restarting the bot service.
     * 
     * @param botType   Bot type
     * @param id        Bot ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceAddBot = function(botType, id, callback) {
        requestJSON("?page=BotServer&add=" + id + "&botType=" + botType, null, callback);        
    };

    /**
     * Request for updating a bot with given type and id. This is used in order to let changes
     * in a bot take place without restarting the bot service.
     * 
     * @param botType   Bot type
     * @param id        Bot ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceUpdateBot = function(botType, id, callback) {
        requestJSON("?page=BotServer&update=" + id + "&botType=" + botType, null, callback);        
    };

    /**
     * Request for deleting a bot with given type and id. This is used in order to let changes
     * in a bot take place without restarting the bot service.
     * 
     * @param botType   Bot type
     * @param id        Bot ID
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceDeleteBot = function(botType, id, callback) {
        requestJSON("?page=BotServer&delete=" + id + "&botType=" + botType, null, callback);        
    };

    /**
     * Send a message to the bot.
     * 
     * @param botType   Bot type
     * @param id        Bot ID
     * @param msg       Message
     * @param callback  Callback function used when the results are ready.
     */
    REST.botServiceSendMessage = function(botType, id, msg, callback) {
        requestJSON("?page=BotServer&msg=" + id + "&botType=" + botType + "&text=" + msg, null, callback);        
    };

}