FROM serversideup/php:8.3-fpm-nginx

ENV S6_CMD_WAIT_FOR_SERVICES=1
ENV PHP_OPCACHE_ENABLE=1
ENV COMPOSER_NO_DEV=1

ARG USER_ID=1000
ARG GROUP_ID=1000

COPY --chmod=755 ./resources/docker/dist/run-new.sh /etc/entrypoint.d/99-bass.sh

COPY ./resources/docker/dist/php.ini /usr/local/etc/php/conf.d/zzz-bass-php.ini

USER root

RUN install-php-extensions imagick bcmath intl

RUN apt update \
    && apt-get install -y \
    sqlite3 \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

RUN docker-php-serversideup-s6-init

USER www-data

ADD --chown=www-data:www-data https://github.com/karlomikus/bar-assistant.git .

ADD --chown=www-data:www-data https://github.com/bar-assistant/data.git ./resources/data

RUN composer install --optimize-autoloader --no-dev \
    && sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/open-api-spec.yml \
    && cp .env.dist .env \
    && php artisan key:generate \
    && php artisan storage:link \
    && php artisan config:cache \
    && php artisan route:cache

VOLUME ["/var/www/html/storage/bar-assistant"]