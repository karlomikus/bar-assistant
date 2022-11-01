#!/bin/bash

set -e

first_time_setup_check() {
    if [ ! -f /var/www/cocktails/.env ]; then
        echo "Application ENV file not found, assuming first time setup..."

        cd /var/www/cocktails

        cp .env.dist .env

        php artisan key:generate
        php artisan storage:link

        if [ ! -f /var/www/cocktails/storage/database.sqlite ]; then
            echo "Creating a new database..."

            touch /var/www/cocktails/storage/database.sqlite
            php artisan migrate --force
            php artisan bar:open
        fi

        echo "Setting permissions..."

        chown -R www-data:www-data /var/www/cocktails

        echo "Done with first time setup..."
    fi
}

start_system() {
    first_time_setup_check
}

start_system

exec apache2-foreground
