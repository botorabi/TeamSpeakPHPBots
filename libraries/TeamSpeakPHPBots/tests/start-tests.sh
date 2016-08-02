#!/bin/sh

# Copyright (c) 2016 by Botorabi. All rights reserved.
# https://github.com/botorabi/TeamSpeakPHPBots
#
# License: MIT License (MIT)
# Read the LICENSE text in main directory for more details.
#
# Created: 1st August 2016
# Author:  Botorabi (botorabi AT gmx DOT net)


# Shell script for unit testing of TeamSpeakPHPBots framework

# Make sure that phpunit is in your path or adapt the line below.
PHP_UNIT=phpunit
$PHP_UNIT --bootstrap bootstrap.php src

# On MS windows this may help you
#PHP_BIN=<path>/php-7.0.9-Win32-VC14-x86/php.exe
#PHP_UNIT=<path>/phpunit-5.4.6.phar
#$PHP_BIN $PHP_UNIT --bootstrap bootstrap.php src
