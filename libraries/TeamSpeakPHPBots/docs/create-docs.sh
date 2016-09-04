#!/bin/sh

# Copyright (c) 2016 by Botorabi. All rights reserved.
# https://github.com/botorabi/TeamSpeakPHPBots
#
# License: MIT License (MIT)
# Read the LICENSE text in main directory for more details.
#
# Created: 1st August 2016
# Author:  Botorabi (botorabi AT gmx DOT net)


# Shell script for creating API docs

PHP_BIN=php
PHP_DOC=phpDocumentor

$PHP_BIN $PHP_DOC --template="responsive-twig" -d ../src -t ./api