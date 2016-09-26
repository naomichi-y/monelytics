FROM php:7.0.11-cli
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

WORKDIR /tmp

RUN curl -sL http://www.debian.or.jp/using/apt/sources.list.http.ftp.jp.debian.org > /etc/apt/sources.list
RUN apt-get update -y && \
  apt-get install -y git zip libmcrypt-dev && \
  curl -sS https://getcomposer.org/installer | php && \
  mv composer.phar /usr/local/bin/composer && \
  composer self-update && \
  apt-get clean
RUN docker-php-ext-install -j$(nproc) \
  mcrypt

VOLUME ["/data"]
WORKDIR /data
RUN useradd -m -s /bin/bash -u 1000 webapp
USER webapp

ENTRYPOINT ["composer"]
CMD ["--help"]
