#!/bin/bash

find /var/www/SF5/kutxa/public/uploads/* -mtime +10 -exec rm -rf {} \;
#find /var/www/SF5/kutxa/public/uploads/* -mtime +10 -ls
