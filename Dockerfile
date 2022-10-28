FROM php:8.1-apache

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
RUN chmod guo+rwx /usr/local/bin/entrypoint

USER www-data:www-data

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/cocktails

COPY --chown=www-data:www-data . .

RUN composer install --optimize-autoloader --no-dev

EXPOSE 80

VOLUME ["/var/www/cocktails/storage", "/var/www/cocktails/database"]

ENTRYPOINT ["entrypoint"]
