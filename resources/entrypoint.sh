#!/bin/bash

set -e

first_time_setup_check() {
    # whoami

    # ls -al /var/www/cocktails

    echo "Checking if database exists..."

    if [ ! -f /var/www/cocktails/storage/database.sqlite ]; then
        echo "Database not found, starting first time setup!"
        touch /var/www/cocktails/storage/database.sqlite

        cd /var/www/cocktails

        # TODO check for existing
        cp .env.dist .env

        php artisan key:generate
        php artisan migrate --force
        php artisan storage:link
        php artisan bar:open
    fi

    echo "Database found, skipping first time setup!"
}

start_system() {
    first_time_setup_check
}

start_system

exec apache2-foreground
