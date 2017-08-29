/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */
/* 
 * Created on : 2nd July, 2016
 * Author     : Botorabi (boto)
*/

// Various log levels which can be used as filter in initLOG.
var LOG_VERBOSE   = 1;
var LOG_DEBUG     = 2;
var LOG_ERROR     = 4;
var LOG_INFO      = 8;
var LOG_ALL       = LOG_VERBOSE | LOG_DEBUG | LOG_ERROR | LOG_INFO;

/**
   Create a logger instance. Pass the desired log levels as logical OR. Pass 0 to
   enable all log level. The returned logger instance has the following functions:

    v(text): log a verbose message
    d(text): log a debug message
    e(text): log an error message
    i(text): log an info message

   @param filter    Logical OR of desired loglevels, or LOG_ALL for enabling all levels.
   @param prefix    This prefix appears on all messages of the logger instance.
   @return          Logger instance
*/
function createLogger(filter, prefix) {
    
    var logger = {};
    logger.filter = filter;
    logger.prefix = prefix;

    // Log verbose message
    logger.v = function(text) {
        if (logger.filter & LOG_VERBOSE)
            console.log(logger.prefix + "(v): " + text);
    };
    // Log debug message
    logger.d = function(text) {
        if (logger.filter & LOG_DEBUG)
            console.log(logger.prefix + "(d): " + text);
    };
    // Log error message
    logger.e = function(text) {
        if (logger.filter & LOG_ERROR)
            console.log(logger.prefix + "(e): " + text);
    };
    // Log info message
    logger.i = function(text) {
        if (logger.filter & LOG_INFO)
            console.log(logger.prefix + "(i): " + text);
    };
    
    return logger;
}
