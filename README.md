# monelytics

monelytics is a household account book web service.
This is an application that has been made in Laravel.

## Web site
https://monelytics.me/

## System components

* PHP 8.5 (with OPcache)
  * Laravel 13
* Nginx 1.31
* MariaDB 12.3 (LTS)
* Redis 8

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

composer, Artisan and PHPUnit run inside `monelytics_php`. The `webapp` user is built
from `WEBAPP_UID` / `WEBAPP_GID` in `.env`, so set them to your own to keep generated
files owned by you; `-u webapp` then writes as that user. The E2E containers share
this checkout, so they are built from the same values -- if they differ, each instance
leaves files the other cannot write (see `tests/e2e/README.md`).

Setup the containers.

```
cp etc/docker/db/db.env.example etc/docker/db/db.env

# please change configuration
cat etc/docker/db/db.env

cp .env.example .env

# please change configuration
cat .env

# set WEBAPP_UID / WEBAPP_GID in .env to the output of id -u / id -g first
docker compose --profile e2e build
docker compose up -d
docker compose exec -u webapp php composer install
docker compose exec -u webapp php php artisan key:generate
docker compose exec -u webapp php php artisan migrate
```

Open the [http://localhost/](http://localhost/) in your browser.

### Upgrading from the MySQL setup

The db service moved from MySQL 5.7 to MariaDB and stores its data in the `dbdata`
volume, so an existing deployment starts with an empty schema. Dump the old database
before switching and restore it afterwards.

```
# on the old setup
docker exec monelytics_db mysqldump -uroot -p --all-databases > dump.sql

# after docker compose up -d
docker compose exec -T db mariadb -uroot -p monelytics < dump.sql
```

Upgrading an existing MariaDB volume across major versions needs the system tables
refreshed afterwards:

```
docker compose exec db mariadb-upgrade -uroot -p
```

### Upgrading an existing .env

Laravel renamed several keys between 5.3 and 13. An existing `.env` keeps working
only after these are renamed, because the old names are no longer read and the
defaults take over silently.

| Old | New | If left unchanged |
|---|---|---|
| `CACHE_DRIVER` | `CACHE_STORE` | falls back to the `redis` store |
| `MAIL_DRIVER` | `MAIL_MAILER` | mail is written to the log instead of sent |
| `SESSION_DRIVER` | unchanged | — |

Two keys also have to be added, since they had no equivalent before:

| Key | Value | Why |
|---|---|---|
| `DB_CONNECTION` | `mysql` | there was no such key before |
| `REDIS_CLIENT` | `predis` | the phpredis extension is not in the image |

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

## TLS certificate

The production host terminates TLS in the `web` container with a Let's Encrypt
certificate. Certbot runs on the host, not in a container, and `compose.yml`
mounts `/etc/letsencrypt` and `/var/www/certbot` into `web` read-only, so nginx
only ever reads what certbot writes.

The `web` container will not start if the certificate is missing, because
`etc/docker/web/default.conf` references it. On a new host, issue it before
bringing the stack up:

```
sudo snap install --classic certbot
sudo mkdir -p /var/www/certbot

# nginx must already answer on port 80 for the HTTP-01 challenge, so bring the
# stack up once with the server block for 443 commented out.
sudo certbot certonly --webroot -w /var/www/certbot -d monelytics.me \
  --email <address> --agree-tos --no-eff-email
```

Renewal is handled by certbot's own `snap.certbot.renew.timer`. The deploy hook
at `/etc/letsencrypt/renewal-hooks/deploy/reload-nginx.sh` reloads nginx inside
the container afterwards:

```sh
#!/bin/sh
set -e
exec /usr/bin/docker exec monelytics_web nginx -s reload
```

Port 443 has to be open both in the host firewall (`/etc/iptables/rules.v4`) and
in the OCI security list for the instance's subnet.

## Test

```
# Run all tests
docker compose exec -u webapp php php artisan test

# e.g. Specify test class
docker compose exec -u webapp php php artisan test --filter ContactControllerTest
```

End-to-end tests run against a separate instance built with the `e2e` compose
profile. See [tests/e2e/README.md](tests/e2e/README.md) for the steps.

### CI

`.github/workflows/test.yml` runs both suites on every push, in two jobs. Each one
brings up the same compose stack used locally, so the PHP, MariaDB and Playwright
versions come from `compose.yml` and `package.json` rather than being pinned again
in the workflow. A failing E2E run uploads `tests/e2e/report` as an artifact.
