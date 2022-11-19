FROM php:8.1-apache

# Add dependencies
RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    bash \
    && apt-get autoremove -y \
    && apt-get clean

RUN docker-php-ext-install opcache

# Setup default apache stuff
RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf
COPY ./resources/apache.conf /etc/apache2/sites-available/000-default.conf
RUN a2enmod rewrite

# Setup custom php config
COPY ./resources/php.ini $PHP_INI_DIR/conf.d/99-bar-assistant.ini

# Add container entrypoint script
COPY ./resources/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/cocktails

RUN git clone https://github.com/karlomikus/bar-assistant.git .

RUN composer install --optimize-autoloader --no-dev

EXPOSE 80

VOLUME ["/var/www/cocktails/storage"]

ENTRYPOINT ["entrypoint"]
