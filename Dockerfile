FROM alpine:latest AS datapack

RUN apk add --no-cache git

WORKDIR /app/data

RUN git clone --depth 1 --branch datapack https://github.com/bar-assistant/data.git .

RUN rm -r .git

FROM serversideup/php:8.3-fpm-nginx AS php-base

ENV S6_CMD_WAIT_FOR_SERVICES=1
ENV PHP_OPCACHE_ENABLE=1
ENV COMPOSER_NO_DEV=1
ENV APP_BASE_DIR=/var/www/cocktails
ENV NGINX_WEBROOT=/var/www/cocktails/public

USER root

RUN install-php-extensions imagick bcmath intl ffi

RUN apt update \
    && apt-get install -y \
    sqlite3 \
    && apt-get install -y --no-install-recommends libvips42 \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

USER www-data

WORKDIR /var/www/cocktails

FROM php-base AS dist

ARG BAR_ASSISTANT_VERSION
ENV BAR_ASSISTANT_VERSION=${BAR_ASSISTANT_VERSION:-develop}

COPY --chmod=755 ./resources/docker/dist/run-new.sh /etc/entrypoint.d/99-bass.sh

COPY ./resources/docker/dist/php.ini /usr/local/etc/php/conf.d/zzz-bass-php.ini

COPY --chown=www-data:www-data . .

COPY --from=datapack --chown=www-data:www-data /app/data ./resources/data

RUN composer install --optimize-autoloader --no-dev \
    && sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/open-api-spec.yml \
    && cp .env.dist .env \
    && php artisan key:generate \
    && php artisan storage:link \
    && php artisan config:cache \
    && php artisan route:cache

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

FROM php-base AS dev

USER root

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN install-php-extensions xdebug

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

RUN docker-php-serversideup-s6-init

USER www-data