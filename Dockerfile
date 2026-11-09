# syntax=docker/dockerfile:1

FROM php:8.4-fpm-alpine

RUN apk add --no-cache \
        libxml2 \
        curl \
        oniguruma \
        unzip \
        su-exec \
    && apk add --no-cache --virtual .build-deps \
        libxml2-dev \
        curl-dev \
        oniguruma-dev \
    && docker-php-ext-install -j2 \
        pdo_mysql \
        mbstring \
        xml \
        curl \
        opcache \
        pcntl \
        posix \
    && apk del .build-deps

COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

WORKDIR /var/www/html

COPY composer.json composer.lock ./
RUN composer install \
    --no-dev \
    --no-scripts \
    --no-autoloader \
    --prefer-dist \
    --no-interaction

COPY . .

RUN mkdir -p \
        storage/app/public \
        storage/framework/cache \
        storage/framework/sessions \
        storage/framework/views \
        storage/logs \
        bootstrap/cache \
    && composer dump-autoload --optimize --no-dev \
    && chown -R www-data:www-data storage bootstrap/cache \
    && rm /usr/bin/composer

COPY docker/php.ini /usr/local/etc/php/conf.d/99-mvmood.ini
COPY docker/php-fpm.conf /usr/local/etc/php-fpm.d/zz-mvmood.conf
COPY docker/entrypoint.sh /usr/local/bin/entrypoint
RUN chmod +x /usr/local/bin/entrypoint

EXPOSE 9000

ENTRYPOINT ["entrypoint"]
CMD ["php-fpm"]
