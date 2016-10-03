# monelytics

monelytics is a household account book web service.
This is an application that has been made in Laravel5.

## Web site
http://monelytics.me/

## System components

* PHP 7.0 (with OPcache)
  * Laravel 5.2
* Nginx 1.1
* MySQL 5.7
* Redis 3.2

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
cd src

# please change configuration
cp .env.sample .env

cd ../docker
cp .env.sample .env

# please change configuration
cat .env

docker-compose build
docker-compose run composer install
docker-compose up -d
docker-compose run artisan migrate
```

Open the [http://localhost:8080/](http://localhost:8080/) in your browser.

### Stop containers

```
docker-compose down
```

### How to use composer

```
docker-compose run --rm composer [COMMAND]

# e.g. Run install of package
docker-compose run --rm composer install
```

### How to use Artisan

```
docker-compose run --rm artisan [COMMAND]

# e.g. Run migration of DB
docker-compose run --rm artisan migrate
```

## Test

```
# Run all tests
docker-compose run --rm phpunit

# e.g. Specify test class
docker-compose run --rm phpunit tests/Controllers/ContactControllerTest
```
