#!/bin/bash
set -e
set -u
./upload.sh ekstraklasa
./upload.sh 1liga/n
./upload.sh 1liga/s
./upload.sh 2liga/nw
./upload.sh 2liga/ne
./upload.sh 2liga/se
./upload.sh 2liga/sw

