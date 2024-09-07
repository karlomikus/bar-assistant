#!/bin/sh

gosu init.sh

php-fpm & nginx -g 'daemon off;'