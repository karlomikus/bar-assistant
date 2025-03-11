FROM alpine:latest AS datapack

RUN apk add --no-cache git

WORKDIR /app/data

RUN git clone --depth 1 --branch v5 https://github.com/bar-assistant/data.git .

RUN rm -r .git

FROM serversideup/php:8.3-fpm-nginx AS php-base

LABEL org.opencontainers.image.source="https://github.com/karlomikus/bar-assistant"
LABEL org.opencontainers.image.description="Bar assistant is a all-in-one solution for managing your home bar"
LABEL org.opencontainers.image.licenses=MIT

ENV S6_CMD_WAIT_FOR_SERVICES=1
ENV APP_BASE_DIR=/var/www/cocktails
ENV NGINX_WEBROOT=/var/www/cocktails/public

USER root

RUN install-php-extensions bcmath intl ffi

RUN apt update \
    && apt-get install -y \
    sqlite3 \
    && apt-get install -y --no-install-recommends libvips42 \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

USER www-data

WORKDIR ${APP_BASE_DIR}

FROM php-base AS dist

ENV PHP_OPCACHE_ENABLE=1
ENV PHP_OPCACHE_MAX_ACCELERATED_FILES=20000
ENV PHP_OPCACHE_MEMORY_CONSUMPTION=256
ARG BAR_ASSISTANT_VERSION
ENV BAR_ASSISTANT_VERSION=${BAR_ASSISTANT_VERSION:-develop}

COPY --chmod=755 ./resources/docker/dist/init.sh /etc/entrypoint.d/99-bass.sh

COPY --chmod=755 --chown=www-data:www-data ./resources/docker/dist/nginx.conf /etc/nginx/server-opts.d/99-bass.conf

USER root

RUN docker-php-serversideup-s6-init

USER www-data

COPY ./resources/docker/dist/php.ini /usr/local/etc/php/conf.d/zzz-bass-php.ini

COPY --chown=www-data:www-data . .

COPY --from=datapack --chown=www-data:www-data /app/data ./resources/data

RUN composer install --optimize-autoloader --no-dev \
    && sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/openapi-generated.yaml \
    && cp .env.dist .env

VOLUME ["$APP_BASE_DIR/storage/bar-assistant"]

FROM php-base AS dev

USER root

ARG USER_ID=1000
ARG GROUP_ID=1000

RUN install-php-extensions xdebug

RUN docker-php-serversideup-set-id www-data $USER_ID:$GROUP_ID && \
    docker-php-serversideup-set-file-permissions --owner $USER_ID:$GROUP_ID --service nginx

RUN docker-php-serversideup-s6-init

USER www-data
