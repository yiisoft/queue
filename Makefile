export COMPOSE_PROJECT_NAME=queue

help:			## Display help information
	@fgrep -h "##" $(MAKEFILE_LIST) | fgrep -v fgrep | sed -e 's/\\$$//' | sed -e 's/##//'

build:			## Build an image from a docker-compose file. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml up -d --build

down:			## Stop and remove containers, networks
	docker-compose -f tests/docker/docker-compose.yml down

sh:			## Enter the container with the application
	docker exec -it queue-php sh

test:			## Run tests. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml build --pull queue-php
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml run queue-php vendor/bin/phpunit --debug
	make down

mutation-test:		## Run mutation tests. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml build --pull queue-php
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml run queue-php php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	make down

coverage:		## Run code coverage. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml run queue-php vendor/bin/phpunit --coverage-clover coverage.xml
	make down

static-analyze:		## Run code static analyze. Params: {{ v=8.1 }}. Default latest PHP 8.1
	PHP_VERSION=$(filter-out $@,$(v)) docker-compose -f tests/docker/docker-compose.yml run queue-php vendor/bin/psalm --config=psalm.xml --shepherd --stats --php-version=$(v)
	make down
