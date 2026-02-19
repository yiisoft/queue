.DEFAULT_GOAL := help

DOCKER_COMPOSE := docker compose \
 	-f docker/compose.yaml \
 	--env-file docker/.env \
 	$(shell test -f docker/.env.local && echo '--env-file docker/.env.local')
export CONTAINER_USER = $(shell id -u):$(shell id -g)

RUN := $(if $(YII_INSIDE_CONTAINER),,$(DOCKER_COMPOSE) run --rm -i php)

shell: ## Open a shell inside the container.
	@if [ -n "$$YII_INSIDE_CONTAINER" ]; then \
		echo "You are already inside a container."; \
		exit 1; \
	fi
	$(RUN) bash

composer: ## Run Composer command: `make composer ARGS=start`
	$(RUN) composer $(ARGS)

test: phpunit
phpunit: ## [test] Run PHPUnit tests: `make phpunit ARGS="--filter=TestName"`
	$(RUN) ./vendor/bin/phpunit $(ARGS)

mutation: infection
infection: ## [infection] Run mutation testing with Infection.
	$(RUN) ./vendor/bin/roave-infection-static-analysis-plugin --threads=max --ignore-msi-with-no-mutations --only-covered

psalm: ## Run Psalm static analysis: `make psalm ARGS="--show-info=true"`
	$(RUN) ./vendor/bin/psalm $(ARGS)

cs-fix: php-cs-fixer
php-cs-fixer: ## [cs-fix] Fix code style with PHP-CS-Fixer: `make php-cs-fixer ARGS="--dry-run"`
	$(RUN) ./vendor/bin/php-cs-fixer fix $(ARGS)

coverage: ## Generate code coverage report in HTML
	$(RUN) ./vendor/bin/phpunit --coverage-html=runtime/coverage
	make down

help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
