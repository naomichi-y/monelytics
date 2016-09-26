FROM php:7.0.11-fpm
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

RUN curl -sL http://www.debian.or.jp/using/apt/sources.list.http.ftp.jp.debian.org > /etc/apt/sources.list
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
