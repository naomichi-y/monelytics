FROM php:7.0.11-cli
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

WORKDIR /tmp

RUN apt-get update && \
  apt-get install -y \
  libmcrypt-dev && \
  apt-get clean
RUN docker-php-ext-install -j$(nproc) \
    mbstring \
    mcrypt \
    pdo \
    pdo_mysql

COPY ./php/php.ini /usr/local/etc/php/php.ini

WORKDIR "/data"
ENTRYPOINT ["php", "artisan"]
CMD ["--help"]
