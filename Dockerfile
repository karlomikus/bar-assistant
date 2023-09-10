FROM php:8.2-fpm as php-base

ARG BAR_ASSISTANT_VERSION
ENV BAR_ASSISTANT_VERSION=${BAR_ASSISTANT_VERSION:-develop}

ARG PGID=1000
ENV PGID=${PGID}
ARG PUID=1000
ENV PUID=${PUID}

# Add php extension manager
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

RUN apt update \
    && apt-get install -y \
    git \
    unzip \
    sqlite3 \
    bash \
    nginx \
    gosu \
    && apt-get autoremove -y \
    && apt-get clean \
    && chmod +x /usr/local/bin/install-php-extensions && \
    install-php-extensions imagick opcache redis zip && \
    echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/www.conf

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

FROM php-base

WORKDIR /var/www/cocktails

COPY . .

# Configure nginx
COPY ./resources/docker/nginx.conf /etc/nginx/sites-enabled/default

# Configure php
COPY ./resources/docker/php.ini $PHP_INI_DIR/php.ini

# Add container entrypoint script
COPY ./resources/docker/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chmod +x /var/www/cocktails/resources/docker/run.sh \
    && sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/open-api-spec.yml \
    && composer install --optimize-autoloader --no-dev \
    && mkdir -p /var/www/cocktails/storage/bar-assistant/

EXPOSE 3000

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

ENTRYPOINT ["entrypoint"]
