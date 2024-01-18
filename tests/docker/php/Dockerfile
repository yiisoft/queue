# Important! Do not use this image in production!

ARG PHP_VERSION
FROM --platform=linux/amd64 php:${PHP_VERSION}-cli-alpine

RUN apk add git autoconf g++ make linux-headers

RUN docker-php-ext-install pcntl
RUN pecl install xdebug pcov
RUN docker-php-ext-enable xdebug pcov

COPY --from=composer:latest /usr/bin/composer /usr/local/bin/composer
ENV COMPOSER_ALLOW_SUPERUSER 1

WORKDIR /app
ENTRYPOINT ["sh", "tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
