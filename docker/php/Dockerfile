FROM php:7.0.11-fpm
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

WORKDIR /tmp

RUN apt-get update && \
  apt-get install -y libmcrypt-dev && \
  apt-get clean
RUN docker-php-ext-install -j$(nproc) \
  opcache \
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
