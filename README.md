# monelytics

monelytics is a household account book web service.
This is an application that has been made in Laravel5.

## Web site
https://monelytics.me/

## System components

* PHP 7.1 (with OPcache)
  * Laravel 5.3
* Nginx 1.27
* MariaDB 10.6
* Redis 7

## Local setup

### Required tools

* [Docker](https://docs.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Setup containers

Application is consists of the following container.

* monelytics_web
* monelytics_php
* monelytics_db
* monelytics_redis

composer, Artisan and PHPUnit run inside `monelytics_php`. Use `-u webapp` so that
generated files keep the host user's ownership.

Setup the containers.

```
cp etc/docker/db/db.env.example etc/docker/db/db.env

# please change configuration
cat etc/docker/db/db.env

cp .env.example .env

# please change configuration
cat .env

docker compose build
docker compose up -d
docker compose exec -u webapp php composer install
docker compose exec -u webapp php php artisan migrate
```

Open the [http://localhost/](http://localhost/) in your browser.

### Stop containers

```
docker compose stop
```

### How to use composer

```
docker compose exec -u webapp php composer [COMMAND]

# e.g. Run install of package
docker compose exec -u webapp php composer install
```

### How to use Artisan

```
docker compose exec -u webapp php php artisan [COMMAND]

# e.g. Run migration of DB
docker compose exec -u webapp php php artisan migrate
```

## Test

```
# Run all tests
docker compose exec -u webapp php vendor/bin/phpunit

# e.g. Specify test class
docker compose exec -u webapp php vendor/bin/phpunit tests/Controllers/ContactControllerTest.php
```
