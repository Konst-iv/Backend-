.PHONY: install test cs psalm fix build deploy check

install:
	composer install

test:
	./vendor/bin/phpunit

cs:
	./vendor/bin/phpcs

psalm:
	./vendor/bin/psalm --use-baseline=psalm-baseline.xml

fix:
	./vendor/bin/php-cs-fixer fix

check: cs psalm test

build: install check

deploy: build

docker-build:
	docker-compose build

docker-up:
	docker-compose up -d

docker-down:
	docker-compose down

docker-logs:
	docker-compose logs -f

rebuild: docker-down docker-build docker-up install

setup: rebuild install

ci: install cs psalm test

clean:
	rm -rf var/cache/* var/log/*

reset: clean docker-down docker-up install

baseline:
	./vendor/bin/psalm --set-baseline=psalm-baseline.xml

update-baseline: baseline
