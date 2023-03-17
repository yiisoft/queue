ARG PHP_VERSION
FROM php:${PHP_VERSION}-fpm-alpine

RUN apk add autoconf g++ make

RUN pecl install pcov
RUN docker-php-ext-enable pcov

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /var/www

CMD ["sleep", "infinity"]
