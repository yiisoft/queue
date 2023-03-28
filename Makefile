export COMPOSE_PROJECT_NAME=yii-queue

build:
	docker-compose -f tests/docker-compose.yml up -d --build

down:
	docker-compose -f tests/docker-compose.yml down

test:
	docker-compose -f tests/docker-compose.yml build --pull php$(v)
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --colors=always -v --debug
	make down

mutation-test:
	docker-compose -f tests/docker-compose.yml build --pull php$(v)
	docker-compose -f tests/docker-compose.yml run php$(v) php -dpcov.enabled=1 -dpcov.directory=. vendor/bin/roave-infection-static-analysis-plugin -j2 --ignore-msi-with-no-mutations --only-covered
	make down

coverage:
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/phpunit --coverage-clover coverage.xml
	make down

static-analyze:
	docker-compose -f tests/docker-compose.yml run php$(v) vendor/bin/psalm --config=psalm.xml --shepherd --stats --php-version=$(v)
	make down

clean:
	docker-compose down
	sudo rm -rf tests/runtime/*
	sudo rm -rf composer.lock
	sudo rm -rf vendor/
	sudo rm -rf tests/runtime/.composer*
