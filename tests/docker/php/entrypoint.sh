#!/bin/sh

set -eu

#flock tests/runtime/composer-install.lock composer update --prefer-dist --no-interaction --no-progress --optimize-autoloader --ansi

php --version
set -x
exec "$@"
