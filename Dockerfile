FROM php:8.1-apache

RUN echo "ServerName localhost" >> /etc/apache2/apache2.conf

RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    vim \
    bash \
    && apt-get autoremove -y \
    && apt-get clean

COPY ./resources/apache.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

COPY ./resources/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

COPY ./resources/php.ini $PHP_INI_DIR/conf.d/99-bar-assistant.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/cocktails

COPY . .

RUN composer install --optimize-autoloader --no-dev

EXPOSE 80

# VOLUME ["/var/www/cocktails/storage"]

ENTRYPOINT ["entrypoint"]
