FROM php:8.5-fpm

WORKDIR /tmp

# opcache と mbstring は php:8.5 に組み込み済みのため入れない
# (docker-php-ext-install に渡すとモジュールを生成せず失敗する)。
# unzip は composer の dist 展開に、git は composer.json の vcs 解決に必要。
RUN set -eux; \
  apt-get update; \
  apt-get install -y --no-install-recommends \
    git \
    unzip \
    libzip-dev \
  ; \
  docker-php-ext-install -j"$(nproc)" \
    pdo_mysql \
    zip \
  ; \
  rm -rf /var/lib/apt/lists/*

# 専用の composer コンテナを廃したため、この php イメージへ同梱する。
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer

# チェックアウトの所有者と uid/gid を揃える。ずれると composer install や
# storage への書き込みが Permission denied になるため、ホスト側の値を渡すこと。
ARG UID=1000
ARG GID=1000
RUN groupadd -g "$GID" webapp \
  && useradd -m -s /bin/bash -u "$UID" -g "$GID" webapp

COPY www.conf /usr/local/etc/php-fpm.d/zz-webapp.conf
COPY php.ini /usr/local/etc/php/conf.d/zz-webapp.ini

WORKDIR /data
