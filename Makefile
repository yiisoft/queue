.DEFAULT_GOAL := help

DOCKER_COMPOSE := docker compose \
 	-f docker/compose.yaml \
 	--env-file docker/.env \
 	$(shell test -f docker/.env.local && echo '--env-file .env.local')
export CONTAINER_USER = $(shell id -u):$(shell id -g)

RUN := $(if $(YII_INSIDE_CONTAINER),,$(DOCKER_COMPOSE) run --rm -i php)

shell:
	@if [ -n "$$YII_INSIDE_CONTAINER" ]; then \
		echo "You are already inside a container."; \
		exit 1; \
	fi
	$(RUN) bash

composer: ## Run Composer command: `make composer ARGS=start`
	$(RUN) composer $(ARGS)

test: phpunit
phpunit:
	$(RUN) ./vendor/bin/phpunit $(ARGS)

mutation: infection
infection:
	$(RUN) ./vendor/bin/roave-infection-static-analysis-plugin --threads=max --ignore-msi-with-no-mutations --only-covered

psalm:
	$(RUN) ./vendor/bin/psalm $(ARGS)

cs-fix: php-cs-fixer
php-cs-fixer:
	$(RUN) ./vendor/bin/php-cs-fixer fix $(ARGS)

coverage:
	$(RUN) ./vendor/bin/phpunit --coverage-html=runtime/coverage
	make down

# Output the help for each task, see https://marmelab.com/blog/2016/02/29/auto-documented-makefile.html
help: ## This help.
	@awk 'BEGIN {FS = ":.*?## "} /^[a-zA-Z_-]+:.*?## / {printf "\033[36m%-30s\033[0m %s\n", $$1, $$2}' $(MAKEFILE_LIST)
