FROM php:7.4-cli

RUN apt-get update \
    && apt-get install -y \
        curl \
        zlib1g-dev \
        libicu-dev \
        libpq-dev \
        libzip-dev

RUN docker-php-ext-install \
        zip \
        intl \
        pcntl \
        bcmath \
        sockets \
        pdo_mysql \
        pdo_pgsql \

RUN docker-php-ext-configure intl && \
    docker-php-ext-configure pgsql -with-pgsql=/usr/local/pgsql && \
    pecl install igbinary && \
    docker-php-ext-enable igbinary

RUN curl -L -o /tmp/composer-setup.php https://getcomposer.org/installer \
    && php /tmp/composer-setup.php --install-dir=/usr/local/bin --filename=composer \
    && rm /tmp/composer-setup.php \
    && curl -L -o /usr/local/bin/php-cs-fixer https://cs.sensiolabs.org/download/php-cs-fixer-v2.phar \
    && chmod a+x /usr/local/bin/php-cs-fixer

COPY . /code
WORKDIR /code

ENTRYPOINT ["tests/docker/php/entrypoint.sh"]
CMD ["sleep", "infinity"]
