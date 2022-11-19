#!/bin/bash

set -e

system_start_checkup() {
    if [ ! -f /var/www/cocktails/.env ]; then
        echo "Application .env file not found, creating a new .env file..."

        cd /var/www/cocktails

        cp .env.dist .env

        php artisan key:generate
        php artisan storage:link

        if [ ! -f /var/www/cocktails/storage/database.sqlite ]; then
            echo "Database not found, creating a new database..."
            touch /var/www/cocktails/storage/database.sqlite
            php artisan migrate --force
            php artisan bar:open
        else
            echo "Database already exists, running migrations..."
            php artisan migrate --force
        fi

        php artisan config:cache
        php artisan route:cache

        echo "Setting permissions..."

        chown -R www-data:www-data /var/www/cocktails

        echo "Application ready!"
    fi
}

start_system() {
    system_start_checkup
}

start_system

exec apache2-foreground
