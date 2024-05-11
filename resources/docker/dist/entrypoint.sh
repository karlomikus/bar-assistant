#!/bin/bash
set -e

# Get PUID/PGID
PUID=${PUID:-1000}
PGID=${PGID:-1000}

cd /var/www/cocktails

echo "
                    ██▒▒██▒▒██  
                      ██▒▒██▒▒  
    ▒▒▒▒▒▒              ██▒▒██  
  ▒▒      ▒▒          ▒▒  ██▒▒  
  ▒▒  ██  ▒▒████████▒▒██    ██  
  ▒▒  ██          ▒▒  ██        
    ▒▒██░░░░░░░░▒▒░░░░██        
        ██░░░░░░░░░░██          
          ██░░░░░░██            
            ██████              
              ██                
              ██                
              ██                
              ██                
              ██                
          ████░░████            "

echo "[ENTRYPOINT] Starting Bar Assistant, this can take a few minutes depending on the system..."
echo "[ENTRYPOINT] User uid: $PUID"
echo "[ENTRYPOINT] User gid: $PGID"

current_uid=$(id -u www-data)

if [ "$current_uid" != "$PUID" ]; then
    echo "[ENTRYPOINT] Updating user and group"
    groupmod -o -g "$PGID" www-data
    usermod -o -u "$PUID" www-data
    echo "[ENTRYPOINT] Updating folder ownership"
    chown -R www-data:www-data /var/www/cocktails
fi

gosu www-data ./resources/docker/dist/run.sh

echo "[ENTRYPOINT] Starting PHP-FPM and nginx"

php-fpm & nginx -g 'daemon off;'

# exec "$@"
