FROM php:8.1-fpm

# Add dependencies
RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    bash \
    nginx \
    gosu \
    && apt-get autoremove -y \
    && apt-get clean

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd opcache redis zip

# Setup custom php config
COPY ./resources/docker/php.ini $PHP_INI_DIR/php.ini

# Setup nginx
COPY ./resources/docker/nginx.conf /etc/nginx/sites-enabled/default

# Add container entrypoint script
COPY ./resources/docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/cocktails

COPY --chown=www-data:www-data . .

RUN composer install --optimize-autoloader --no-dev

RUN mkdir -p /var/www/cocktails/storage/bar-assistant/temp

EXPOSE 3000

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

ENTRYPOINT ["entrypoint"]
