#!/bin/sh

# Copyright (c) 2016 by Botorabi. All rights reserved.
# https://github.com/botorabi/TeamSpeakPHPBots
#
# License: MIT License (MIT)
# Read the LICENSE text in main directory for more details.
#
# Created: 1st August 2016
# Author:  Botorabi (botorabi AT gmx DOT net)

if [ "$#" -ne 1 ]; then
  echo "  "
  echo "*** Cannot create API documentation!"
  echo "    Use: create-docs.sh <API version number>"
  exit
fi

# Shell script for creating API docs

PHP_DOC=../../../vendor/phpdocumentor/phpdocumentor/bin/phpdoc
DOC_TITLE_BASE='TeamSpeakPHPBots - Version '
DOC_TITLE="$DOC_TITLE_BASE $1"

$PHP_DOC run --template="responsive-twig" --title="$DOC_TITLE" -d ../src -t ./api-$1
