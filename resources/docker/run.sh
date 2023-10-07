#!/bin/bash

set -e

first_time_check() {
    mkdir -p /var/www/cocktails/storage/bar-assistant/uploads/{cocktails,ingredients,temp}
    mkdir -p /var/www/cocktails/storage/bar-assistant/backups

    if [ ! -f /var/www/cocktails/.env ]; then
        cp .env.dist .env

        php artisan key:generate
        php artisan storage:link

        if [[ $DB_CONNECTION == "sqlite" && $DB_DATABASE ]]; then
            if [ ! -f "$DB_DATABASE" ]; then
                echo "SQLite database not found, creating a new one..."
                touch "$DB_DATABASE"
            fi
        fi
    fi
}

start_system() {
    first_time_check

    php artisan migrate --force --isolated

    php artisan bar:refresh-search --clear

    echo "Adding routes and config to cache..."

    php artisan config:cache
    php artisan route:cache

    echo "!***************************!"
    echo "!    Application ready      !"
    echo "!***************************!"
}

start_system
