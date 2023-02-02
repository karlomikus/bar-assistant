#!/bin/bash

set -e

migrate_existing_folder() {
    # TODO: Backup existing data

    if [ -d "/var/www/cocktails/storage/uploads" ]; then
        echo "Found existing uploads folder, moving to new location..."
        mkdir -p /var/www/cocktails/storage/bar-assistant/uploads/cocktails
        mkdir -p /var/www/cocktails/storage/bar-assistant/uploads/ingredients

        mv /var/www/cocktails/storage/uploads /var/www/cocktails/storage/bar-assistant/uploads
    fi

    if [ -f /var/www/cocktails/storage/database.sqlite ]; then
        echo "Found database, moving to new location..."
        mv /var/www/cocktails/storage/database.sqlite /var/www/cocktails/storage/bar-assistant/database.sqlite
    fi
}

first_time_check() {
    if [ ! -f /var/www/cocktails/.env ]; then
        cd /var/www/cocktails

        cp .env.dist .env

        php artisan key:generate
        php artisan storage:link

        if [ ! -f /var/www/cocktails/storage/bar-assistant/database.sqlite ]; then
            echo "Database not found, creating a new database..."
            touch /var/www/cocktails/storage/bar-assistant/database.sqlite
            php artisan migrate --force
            php artisan bar:open
        else
            echo "Database already exists, running migrations..."
            php artisan migrate --force
        fi
    fi
}

start_system() {
    migrate_existing_folder

    first_time_check

    php artisan bar:refresh-search

    echo "Adding routes and config to cache..."

    php artisan config:cache
    php artisan route:cache

    echo "Application ready!"
}

start_system

exec php-fpm
