#!/bin/bash
set -e
set -u
scp -r *.php .htaccess css sklady pzbs:~/liga/$1/
