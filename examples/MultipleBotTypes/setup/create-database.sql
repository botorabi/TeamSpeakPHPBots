/**
 * Copyright (c) 2016-2017 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */


/**
 * This SQL script creates the tables for the example application GreetingBot.
 *
 * Created: 2nd August 2016
 * Author:  Botorabi
 */


/**
 * Create the user table.
 *
 * NOTE: The table name prefix 'tsphpbots_' is defined in Configuration.php
 *       All our table names must have this prefix.
 */

/*DROP TABLE IF EXISTS `tsphpbots_user`;*/
CREATE TABLE IF NOT EXISTS `tsphpbots_user` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  `description` varchar(256) DEFAULT '',
  `active` TINYINT(1) DEFAULT '1',
  `email` varchar(45) DEFAULT NULL,
  `login` varchar(45) NOT NULL,
  `password` varchar(45) DEFAULT NULL,
  `lastLogin` int(11) DEFAULT '0',
  `roles` int(11) DEFAULT '0',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`),
  UNIQUE KEY `login_UNIQUE` (`login`),
  UNIQUE KEY `email_UNIQUE` (`email`)
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='TeamSpeakPHPBot: User Table';

/* Create a default Admin user (un: admin, pw: admin) */
INSERT INTO `tsphpbots_user` (`id`, `name`, `description`, `active`, `email`, `login`, `password`, `lastLogin`, `roles`) VALUES ('1', 'Admin', 'Administrator', '1', '', 'admin', '21232f297a57a5a743894a0e4a801fc3', '0', '1');


/*
 * Create a table for GreetingBot.
 *
 * The following fields are needed by the framework and must exsit in every bot table:
 *
 * id
 * botTye
 * name
 * description
 * active
 *
 * The field 'greetingText' is bot specific.
 */

/*DROP TABLE IF EXISTS `tsphpbots_greetingbot`;*/
CREATE TABLE IF NOT EXISTS `tsphpbots_greetingbot` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `botType` varchar(45) DEFAULT '',
  `name` varchar(45) DEFAULT '',
  `description` varchar(256) DEFAULT '',
  `active` TINYINT(1) DEFAULT '1',
  `greetingText` varchar(256) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='TeamSpeakPHPBot: GreetingBot table';

/*
 * Create a table for ChatBot.
 */

/*DROP TABLE IF EXISTS `tsphpbots_chatbot`;*/
CREATE TABLE IF NOT EXISTS `tsphpbots_chatbot` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `botType` varchar(45) DEFAULT '',
  `name` varchar(45) DEFAULT '',
  `description` varchar(256) DEFAULT '',
  `active` TINYINT(1) DEFAULT '1',
  `nickName` varchar(45) DEFAULT 'Chatty',
  `channelID` int(11) DEFAULT '0',
  `greetingText` varchar(256) DEFAULT '',
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='TeamSpeakPHPBot: ChatBot table';

