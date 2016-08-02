/**
 * Copyright (c) 2016 by Botorabi. All rights reserved.
 * https://github.com/botorabi/TeamSpeakPHPBots
 * 
 * License: MIT License (MIT), read the LICENSE text in
 *          main directory for more details.
 */


/**
 * This SQL script creates the necessary tables for the TeamSpeakPHPBot framework's unit tests.
 *
 * Created: 16th June 2016
 * Author:  Botorabi
 */


/**
 * Create the user table.
 * Make sure that the table name prefix set in Configuration.php is considered
 * and adapt the table name 'user' below if a prefix is used.
 */
DROP TABLE IF EXISTS `user`;
CREATE TABLE `user` (
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
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='User Table used for automated testing.';


DROP TABLE IF EXISTS `dbobject`;
CREATE TABLE `dbobject` (
  `id` int(11) unsigned NOT NULL AUTO_INCREMENT,
  `name` varchar(45) NOT NULL,
  PRIMARY KEY (`id`),
  UNIQUE KEY `id_UNIQUE` (`id`)
)
ENGINE=InnoDB AUTO_INCREMENT=1 DEFAULT CHARSET=utf8 COMMENT='DBObject Table used for automated testing.';

INSERT INTO `user` (`id`, `name`, `description`, `active`, `email`, `login`, `password`, `lastLogin`, `roles`) VALUES ('1', 'Admin', 'Administrator', '1', 'myemail@mail.com', 'admin', '21232f297a57a5a743894a0e4a801fc3', '0', '1');