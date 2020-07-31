UID = $(id -u):$(id -g)

build:
	CURRENT_UID=$(value UID) docker-compose build
composer:
	CURRENT_UID=$(value UID) docker-compose run --rm php composer install
install:
	CURRENT_UID=$(value UID) docker-compose build
	CURRENT_UID=$(value UID) docker-compose run --rm php composer install
run:
	CURRENT_UID=$(value UID) docker-compose up
