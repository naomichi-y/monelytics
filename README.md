# monelytics

money + analytics = monelytics

## Web site
http://monelytics.me/

## Local setup

### Required tools

* [Docker](https://docs.docker.com/)
* [Docker Compose](https://docs.docker.com/compose/)

### Setup containers

Application is consists of the following container.

* monelytics_data
* monelytics_php
* monelytics_web
* monelytics_db
* monelytics_composer
* monelytics_artisan

Setup the containers.

```
cd docker
docker-compose build
docker-compose up -d
docker-compose run composer install
docker-compose run artisan migrate
```

Open the http://localhost:8080/ in your browser.

## How to use composer

```
docker-compose run composer [COMMAND]

# e.g.
docker-compose run composer install
```

## How to use Artisan

```
docker-compose run artisan [COMMAND]

# e.g.
docker-compose run artisan migrate
```
