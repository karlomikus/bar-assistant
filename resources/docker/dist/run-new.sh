#!/bin/sh

set -e

mkdir -p /var/www/cocktails/storage/bar-assistant/uploads/{cocktails,ingredients,temp}
mkdir -p /var/www/cocktails/storage/bar-assistant/backups

db_file=/var/www/cocktails/storage/bar-assistant/database.ba3.sqlite

cd /var/www/cocktails

if [ ! -f "$db_file" ]; then
    echo "[BAR-ASSISTANT] SQLite database not found, creating a new one..."
    touch "$db_file"
fi

# Enable WAL mode
echo "[BAR-ASSISTANT] Enabling database WAL mode..."
sqlite3 "$db_file" 'pragma journal_mode=wal;'

# Setup laravel
php artisan storage:link
php artisan config:cache
php artisan route:cache
# Setup Meilisearch ENV variables
php artisan bar:setup-meilisearch
php artisan scout:sync-index-settings
# Run DB setup
php artisan migrate --force
# Update meilisearch indexes
php artisan bar:refresh-search

echo '
                               
    __                         
   |@@@g_                      
   |@@ <@@g_              ~~,  
   |@@   @@@@a_           @@|  
   |@@   @@@@@@@_         @@|  
   |@@   @@@@@@@@@@@@@@@@@@@|  
   |@@   @@@@@@@@"        @@|  
   |@@   @@@@@P           @@|  
   |@@ _~@@P              @@|  
   |@@@@P                      
   '""'                        
                               
   ggggggggggggg               
   BBBBBBBBBBBBN               
                               
'

echo "[BAR-ASSISTANT] Bar Assistant API ready"
