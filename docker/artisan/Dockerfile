FROM php:7.0.11-fpm
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

RUN curl -sL http://www.debian.or.jp/using/apt/sources.list.http.ftp.jp.debian.org > /etc/apt/sources.list \
  && apt-get update
RUN apt-get install -y \
  libmcrypt-dev
RUN docker-php-ext-install -j$(nproc) \
    mbstring \
    mcrypt \
    pdo \
    pdo_mysql

COPY ./php/php.ini /usr/local/etc/php/php.ini

RUN curl -sS https://getcomposer.org/installer | php
RUN mv composer.phar /usr/local/bin/composer
RUN mkdir -p /var/www/monelytics
RUN ls -la /var/www/monelytics
#RUN cd /var/www/monelytics/ && php artisan migrate
