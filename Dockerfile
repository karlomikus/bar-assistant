FROM php:8.2-fpm

ARG BAR_ASSISTANT_VERSION
ENV BAR_ASSISTANT_VERSION=${BAR_ASSISTANT_VERSION:-develop}

ARG PGID=33
ENV PGID=${PGID}
ARG PUID=33
ENV PUID=${PUID}

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
    install-php-extensions imagick opcache redis zip

# Setup custom php config
COPY ./resources/docker/php.ini $PHP_INI_DIR/php.ini

# Setup nginx
COPY ./resources/docker/nginx.conf /etc/nginx/sites-enabled/default
RUN echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/www.conf

# Add container entrypoint script
COPY ./resources/docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

USER $PUID:$PGID

WORKDIR /var/www/cocktails

COPY --chown=$PUID:$PGID . .

RUN sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/open-api-spec.yml

RUN chmod +x /var/www/cocktails/resources/docker/run.sh

RUN composer install --optimize-autoloader --no-dev

RUN mkdir -p /var/www/cocktails/storage/bar-assistant/

EXPOSE 3000

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

USER root:root

ENTRYPOINT ["entrypoint"]
CMD ["/bin/bash", "-c", "php-fpm & nginx -g 'daemon off;'"]
