#!/bin/bash
set -e
set -u
echo ""
echo $1
rsync -rv --exclude=tdd/translations.json .htaccess css sklady tdd pzbs:~/liga/$1/
