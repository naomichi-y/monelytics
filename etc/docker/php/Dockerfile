# arm64 対応のため、php:7.0.11-fpm (amd64 のみ) から php:7.1-fpm へ変更した。
# mcrypt は PHP 7.2 で削除されるため、Laravel 5.3 を動かせる上限が 7.1 となる。
FROM php:7.1-fpm

WORKDIR /tmp

# ベースの Debian Buster は EOL となり通常のミラーから削除されているため、
# apt の参照先を archive.debian.org へ切り替える。
# unzip は composer の dist 展開に、git は composer.json の vcs リポジトリ解決に必要。
RUN set -eux; \
  sed -i \
    -e 's|http://deb.debian.org/debian|http://archive.debian.org/debian|g' \
    -e 's|http://security.debian.org/debian-security|http://archive.debian.org/debian-security|g' \
    -e 's|http://security.debian.org|http://archive.debian.org|g' \
    -e '/buster-updates/d' \
    /etc/apt/sources.list; \
  echo 'Acquire::Check-Valid-Until "false";' > /etc/apt/apt.conf.d/99no-check-valid-until; \
  apt-get update; \
  apt-get install -y --no-install-recommends libmcrypt-dev git unzip; \
  rm -rf /var/lib/apt/lists/*

RUN docker-php-ext-install -j"$(nproc)" \
  opcache \
  mbstring \
  mcrypt \
  pdo \
  pdo_mysql

# 専用の composer コンテナを廃したため、この php イメージへ同梱する。
# 2.2 は PHP 7.1 をサポートする最後の系列 (2.3 以降は 7.2.5 以上が必須)。
COPY --from=composer:2.2 /usr/bin/composer /usr/bin/composer

# チェックアウトの所有者と uid/gid を揃える。ずれると composer install や
# storage への書き込みが Permission denied になるため、ホスト側の値を渡すこと。
ARG UID=1000
ARG GID=1000
RUN groupadd -g "$GID" webapp \
  && useradd -m -s /bin/bash -u "$UID" -g "$GID" webapp

COPY www.conf /usr/local/etc/php-fpm.d/zz-webapp.conf
COPY php.ini /usr/local/etc/php/conf.d/zz-webapp.ini

WORKDIR /data
