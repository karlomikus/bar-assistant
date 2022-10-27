FROM php:8.1-apache

ENV APP_URL=http://localhost
ENV MEILISEARCH_HOST=
ENV MEILISEARCH_KEY=

RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    vim \
    && apt-get autoremove -y \
    && apt-get clean

COPY ./resources/apache.conf /etc/apache2/sites-available/000-default.conf

RUN a2enmod rewrite

USER www-data:www-data

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

WORKDIR /var/www/cocktails

COPY --chown=www-data:www-data . .

RUN touch database/database.sqlite
RUN mv .env.dist .env

RUN composer install

RUN php artisan key:generate \
    && php artisan migrate:refresh --force

EXPOSE 80

CMD apache2-foreground
