#!/bin/sh

# Copyright (c) 2016 by Botorabi. All rights reserved.
# https://github.com/botorabi/TeamSpeakPHPBots
#
# License: MIT License (MIT)
# Read the LICENSE text in main directory for more details.
#
# Created: 1st August 2016
# Author:  Botorabi (botorabi AT gmx DOT net)


# Shell script for starting the GreetingBot example

# Make sure that php is in your path or adapt the line below.
PHP_BIN=php

# Start the service in background and create a pid and log file
#$PHP_BIN greetingbot.php > botserver.log 2>&1  & echo $! > botserver.pid

#Start the service in foreground
$PHP_BIN botserver.php 2>&1

