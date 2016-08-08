    Copyright (c) 2016 by Botorabi. All rights reserved.
    https://github.com/botorabi/TeamSpeakPHPBots

    License: MIT License (MIT)
    Read the LICENSE text in main directory for more details.

    Created: 4st August 2016
    Author:  Botorabi (botorabi AT gmx DOT net)

# Multiple Bot Types
Here you find an example which demonstrates how to use the framework for hosting multiple bot types. We recommend that you have a look on GreetingBot example first, it is reused here.
An additional bot is introduced: ChatBot. One can chat with the ChatBot and request for information. Send it a message with the text "help" in order to get a list with its supported commands.
As in greeting bot example, this example also contains HTML and Javascript files and provides a full web interface for user management, bot server control, and bot configuration.

# Setup

- Adapt the file *config/Configuration.php* to your needs.

- Create the necessary database and tables using the SQL script in *setup* directory.

- Setup a web server (e.g. Apache or the Built-In server of PHP) for serving this directory (index.php should be the entry).

- Visit the web interface, the default user name and password is admin/admin if you have used the SQL script in *setup* directory. Create a GreetingBot in the bot configuration page.

- Go to *src* directory and start the bot server by using the script *start_botserver.sh*. Alternatively, you can start/stop the bot server using the web interface.

- Play with bots' configuration, the changes are immediately reflected in the bot while the bot server is running (see the console output of the bot server).

# Special Thanks
We want to thank the team behind [TeamSpeak3 PHP Framework] for their great work. Furthermore, we thank [Plainicon] for the images we have used for the web pages in this example. Last but not least, we thank [jQuery] and [jQuery UI] which gave the web pages a good look&feel.

[TeamSpeak3 PHP Framework]: https://github.com/planetteamspeak/ts3phpframework
[Plainicon]: http://plainicon.com
[jQuery]: http://www.jquery.com
[jQuery UI]: http://www.jqueryui.com
