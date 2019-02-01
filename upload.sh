#!/bin/bash
set -e
set -u
echo ""
echo $1
scp -r .htaccess css sklady tdd pzbs:~/liga/$1/
