build:
	docker compose build
composer:
	docker compose run --rm php composer install
install:
	docker compose build
	docker compose run --rm php composer install
run:
	docker compose up
test:
	docker compose run --rm php ./vendor/bin/phpunit
