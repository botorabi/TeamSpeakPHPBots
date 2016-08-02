    Copyright (c) 2016 by Botorabi. All rights reserved.
    https://github.com/botorabi/TeamSpeakPHPBots

    License: MIT License (MIT)
    Read the LICENSE text in main directory for more details.

    Created: 1st August 2016
    Author:  Botorabi (botorabi AT gmx DOT net)

# Introduction

The unit tests of the framework **TeamSpeakPHPBots** are performed using PHPUnit.
The tests need a database and a user with permission to create, delete, and
modify tables in this database. So please create such a database and user.

For test setup go through following points.

- Adapt the configuration file *TestConfiguration.php*
   Define the database name and the user account for accesing it in the array
   *$TSPHPBOT_CONFIG_DB*. If you define a *tablePrefix* then adapt also the
   SQL script *create-testdatabase.sql* which is used during the test start in order
   to create fresh tables used for testing.

- Adapt and use the shell script *start-tests* in this directory to start the tests.

