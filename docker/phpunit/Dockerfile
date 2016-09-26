FROM php:7.0.11-cli
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

WORKDIR /tmp

RUN apt-get update -y && \
  apt-get install -y wget libmcrypt-dev && \
  wget https://phar.phpunit.de/phpunit.phar && \
  apt-get remove -y wget && \
  rm -rf /var/lib/apt/lists/* && \
  chmod +x phpunit.phar && \
  mv phpunit.phar /usr/local/bin/phpunit
RUN docker-php-ext-install -j$(nproc) \
  mbstring \
  mcrypt \
  pdo \
  pdo_mysql

RUN mkdir -p /data
VOLUME ["/data"]
WORKDIR /data

ENTRYPOINT ["phpunit"]
