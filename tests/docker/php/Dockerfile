# Important! Do not use this image in production!

ARG PHP_VERSION
FROM php:${PHP_VERSION}-cli-alpine

RUN apk add git autoconf g++ make linux-headers && \
    docker-php-ext-install pcntl && \
    pecl install xdebug pcov && \
    docker-php-ext-enable xdebug pcov

ADD ./tests/docker/php/php.ini /usr/local/etc/php/conf.d/40-custom.ini

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app
RUN git config --global --add safe.directory /app
ENTRYPOINT ["sh", "tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
