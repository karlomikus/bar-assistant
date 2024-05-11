FROM php:8.2-fpm as php-base

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
    cron \
    && chmod +x /usr/local/bin/install-php-extensions \
    && install-php-extensions imagick opcache redis zip pcntl bcmath \
    && echo "access.log = /dev/null" >> /usr/local/etc/php-fpm.d/www.conf \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

# Add composer
COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer

# Configure php
COPY ./resources/docker/dist/php.ini $PHP_INI_DIR/php.ini

WORKDIR /var/www/cocktails

CMD ["php-fpm"]

FROM php-base as dist

ARG BAR_ASSISTANT_VERSION
ENV BAR_ASSISTANT_VERSION=${BAR_ASSISTANT_VERSION:-develop}

RUN apt update \
    && apt-get install -y \
    nginx \
    gosu \
    supervisor \
    && apt-get autoremove -y \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/* /tmp/* /var/tmp/*

WORKDIR /var/www/cocktails

COPY . .

ADD https://github.com/bar-assistant/data.git ./resources/data

RUN composer install --optimize-autoloader --no-dev

# Configure nginx
COPY ./resources/docker/dist/nginx.conf /etc/nginx/sites-enabled/default

# Add container entrypoint script
COPY ./resources/docker/dist/entrypoint.sh /usr/local/bin/entrypoint

RUN chmod +x /usr/local/bin/entrypoint \
    && chmod +x /var/www/cocktails/resources/docker/dist/run.sh \
    && sed -i "s/{{VERSION}}/$BAR_ASSISTANT_VERSION/g" ./docs/open-api-spec.yml \
    && mkdir -p /var/www/cocktails/storage/bar-assistant/ \
    && echo "* * * * * www-data cd /var/www/cocktails && php artisan schedule:run >> /dev/null 2>&1" >> /etc/crontab \
    && chown -R www-data:www-data /var/www/cocktails

EXPOSE 3000

VOLUME ["/var/www/cocktails/storage/bar-assistant"]

ENTRYPOINT ["entrypoint"]

FROM php-base as localdev

RUN useradd -G www-data,root -u $PUID -d /home/developer developer
RUN mkdir -p /home/developer/.composer && \
    chown -R developer:developer /home/developer

RUN install-php-extensions xdebug

RUN echo "* * * * * developer cd /var/www/cocktails && php artisan schedule:run >> /dev/null 2>&1" >> /etc/crontab

USER developer

WORKDIR /var/www/cocktails

EXPOSE 9000

CMD ["php-fpm"]
