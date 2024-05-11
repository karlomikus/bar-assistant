#!/bin/bash

set -e

db_file=./storage/bar-assistant/database.ba3.sqlite

first_time_check() {
    mkdir -p /var/www/cocktails/storage/bar-assistant/uploads/{cocktails,ingredients,temp}
    mkdir -p /var/www/cocktails/storage/bar-assistant/backups

    if [ ! -f /var/www/cocktails/.env ]; then
        cp .env.dist .env

        php artisan key:generate
        php artisan storage:link

        if [[ $DB_CONNECTION == "sqlite" ]]; then
            if [ ! -f "$db_file" ]; then
                echo "[ENTRYPOINT] SQLite database not found, creating a new one..."
                touch "$db_file"
            fi
        fi
    fi
}

start_system() {
    first_time_check

    php artisan migrate --force --isolated

    # Enable WAL mode
    echo "[ENTRYPOINT] Enabling database WAL mode..."
    sqlite3 "$db_file" 'pragma journal_mode=wal;'

    php artisan bar:refresh-search --clear

    echo "[ENTRYPOINT] Adding routes and config to cache..."

    php artisan config:cache
    php artisan route:cache

    echo "[ENTRYPOINT] Application ready"
}

start_system

# exec "$@"
