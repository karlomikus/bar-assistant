#!/bin/bash
set -e

# Get PUID/PGID
PUID=${PUID:-1000}
PGID=${PGID:-1000}

cd /var/www/cocktails

echo "Starting Bar Assistant, this can take a few minutes depending on the system..."

echo "
User uid:    $PUID
User gid:    $PGID
"

groupmod -o -g $PGID www-data
usermod -o -u $PUID www-data
chown -R www-data:www-data /var/www/cocktails

gosu www-data ./resources/docker/dist/run.sh

php-fpm & nginx -g 'daemon off;'

# exec "$@"
