#!/bin/bash
set -e

echo "Starting Bar Assistant, this can take a few minutes depending on the system..."

echo "
User uid:    $PUID
User gid:    $PGID
"

gosu www-data ./resources/docker/dist/run.sh

php-fpm & nginx -g 'daemon off;'

# exec "$@"
