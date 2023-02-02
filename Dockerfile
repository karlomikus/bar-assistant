FROM php:8.1-fpm

# Add dependencies
RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    bash \
    && apt-get autoremove -y \
    && apt-get clean

ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/
RUN chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions gd opcache redis zip

# Setup custom php config
COPY ./resources/php.ini $PHP_INI_DIR/conf.d/99-bar-assistant.ini

# Add container entrypoint script
COPY ./resources/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# RUN adduser --system --no-create-home --group www
# USER www:www

WORKDIR /var/www/cocktails

COPY . .

RUN composer install --optimize-autoloader --no-dev

RUN mkdir -p /var/www/cocktails/storage/bar-assistant

EXPOSE 9000

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

ENTRYPOINT ["entrypoint"]
