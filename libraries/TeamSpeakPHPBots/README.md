    Copyright (c) 2016-2017 by Botorabi. All rights reserved.
    https://github.com/botorabi/TeamSpeakPHPBots

    License: MIT License (MIT)
    Read the LICENSE text in main directory for more details.

    Created: 2st August 2016
    Author:  Botorabi (botorabi AT gmx DOT net)

# Introduction

**TeamSpeakPHPBots** is a PHP based framework which provides a convenient way to develop TeamSpeak bots and manage them by using a web browser.
Your application will need a copy of the library [TeamSpeak3 PHP Framework] which is used by the framework for communicating with TeamSpeak server. 

It contains the following **features**:

- A **runtime environment** where bots live and interact with the TeamSpeak server.

- A **web interface** allowing creation, deletion and modification of bots.

- A web interface for **user management**.

- A simple to use **persistence** layer using a database for storing and retrieving bot configuration.


An application using TeamSpeakPHPBots library needs a proper configuration and at least one database table for managing its users.

In directory *setup* you will find a configuration template *Configuration.php.Template* which you can copy and adapt for your application.

The file *create-database.sql* in directory *setup* contains a minimum of database information which the framework needs.

Please notice that depending on your application you may need more tables for managing the configuration of your bots.

The directory *web* contains the default web resources which are usually overridden by your application's web resources.

The directory *src* contains the PHP sources of the framework.

The directory *tests* contains unit tests.

The directory *docs* contains the API documentation.

See the provided examples which demonstrate how to use this framework.


[TeamSpeak3 PHP Framework]: https://github.com/planetteamspeak/ts3phpframework

