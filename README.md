    Copyright (c) 2016 by Botorabi. All rights reserved.
    https://github.com/botorabi/TeamSpeakPHPBots

    License: MIT License (MIT)
    Read the LICENSE text in main directory for more details.

    Current Version:   0.9.5
    First Created:     2st August 2016
    Author:            Botorabi (botorabi AT gmx DOT net)


# TeamSpeakPHPBots

TeamSpeakPHPBots is a lightweight framework for developing TeamSpeak Bots in PHP.
The framework is used in a German multi-gaming community and builds the ground for currently over 30 Bot instances of various types.
Its features and stability were enhanced over the time by observing and reflecting the demands and constraints of a real field environment with over 200 simultaneously connected users at peak times.

Here are some of major services provided by the mentioned gaming community Bots:

- Dynamic TeamSpeak Channel Management: automatic channel creation / deletion depending on user load

- Unified Chat System: integrated Telegram, Web Chat, and TeamSpeak into one single chat system consisting of multiple rooms with respect to individual authentications and permissions

- Integration with Community Forum: synchronized authentication and permissions with [Woltlab's Burning Board]


# Introduction

**TeamSpeakPHPBots** is a PHP based framework which provides a convenient way to develop TeamSpeak bots and manage them using a web browser.
It uses the library [TeamSpeak3 PHP Framework] for communicating with TeamSpeak server.

The framework provides the following **features**:

- A **runtime environment** where bots live and interact with the TeamSpeak server.

- A **web interface** allowing creation, deletion and modification of bots.

- A web interface for **user management**.

- A simple to use **persistence** layer using a database for storing and retrieving bot configuration.

See the accompanying **examples** directory which demonstrates how to use this framework.

**Here are a few screenshots showing the web interface.** 
 
![](https://cloud.githubusercontent.com/assets/11502867/17465071/546ff488-5ced-11e6-982d-58f1acf15195.png) 
 
![](https://cloud.githubusercontent.com/assets/11502867/17465072/57a1a976-5ced-11e6-95cc-e1775af7107d.png) 
 
![](https://cloud.githubusercontent.com/assets/11502867/17465073/59a1bc98-5ced-11e6-984b-28220f2bf026.png) 
 
![](https://cloud.githubusercontent.com/assets/11502867/17465074/5b508272-5ced-11e6-8aaa-37bff4a68321.png) 
 
![](https://cloud.githubusercontent.com/assets/11502867/17465075/5cda687e-5ced-11e6-8dfb-27a843d5bd51.png) 

[TeamSpeak3 PHP Framework]: https://github.com/planetteamspeak/ts3phpframework

[Woltlab's Burning Board]: https://www.woltlab.com

