# monelytics

monelytics is a household account book web service.
This is an application that has been made in Laravel5.

## Web site
http://monelytics.me/

## Local setup

### Required tools

* [Docker](https://docs.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Setup containers

Application is consists of the following container.

* monelytics_data
* monelytics_web
* monelytics_php
* monelytics_composer
* monelytics_artisan
* monelytics_phpunit
* monelytics_db
* monelytics_redis

Setup the containers.

```
cd docker
cp .env.sample .env

# please change settings
cat .env

docker-compose build
docker-compose up -d
docker-compose run composer install
docker-compose run artisan migrate
```

Open the [http://localhost:8080/](http://localhost:8080/) in your browser.

### How to use composer

```
docker-compose run composer [COMMAND]

# e.g. Run install of package
docker-compose run composer install
```

### How to use Artisan

```
docker-compose run artisan [COMMAND]

# e.g. Run migration of DB
docker-compose run artisan migrate
```

## Test

```
# Run all tests
docker-compose run phpunit

# e.g. Specify test class
docker-compose run phpunit app/tests/Controllers/ContactControllerTest
```
