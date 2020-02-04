#!/bin/sh

set -eu

flock tests/runtime/composer-install.lock composer install --prefer-dist --no-interaction

tests/docker/wait-for-it.sh redis:6379 -t 180

tests/docker/wait-for-it.sh rabbitmq:5672 -t 180

php --version
set -x
exec "$@"
