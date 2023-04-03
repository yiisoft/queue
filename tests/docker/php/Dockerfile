ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add git autoconf g++ make linux-headers

RUN pecl install xdebug pcov
RUN docker-php-ext-enable xdebug pcov

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app
ENTRYPOINT ["sh", "tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
