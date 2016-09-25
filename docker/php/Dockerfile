FROM php:7.0.11-fpm
MAINTAINER Naomichi Yamakita

RUN curl -sL http://www.debian.or.jp/using/apt/sources.list.http.ftp.jp.debian.org > /etc/apt/sources.list \
  && apt-get update
RUN apt-get install -y \
  libmcrypt-dev
RUN docker-php-ext-install -j$(nproc) \
    mbstring \
    mcrypt \
    pdo \
    pdo_mysql

VOLUME ["/var/run/php-fpm"]

RUN useradd -m -s /bin/bash -u 1000 webapp
RUN mkdir -p /var/run/php-fpm
RUN chown webapp:webapp /var/run/php-fpm

COPY ./php/php.ini /usr/local/etc/php/php.ini
COPY ./php-fpm/www.conf /usr/local/etc/php-fpm.d/zzz-www.conf

RUN cd /var/www/monelytics/ && p^C artisan migrate
