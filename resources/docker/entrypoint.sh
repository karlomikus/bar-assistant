#!/bin/bash

set -e

cd /var/www/cocktails

gosu www-data ./resources/docker/run.sh

exec "$@"
