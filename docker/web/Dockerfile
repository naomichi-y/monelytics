FROM nginx:1.11.4
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

WORKDIR /tmp

RUN useradd -m -s /bin/bash -u 1000 webapp
RUN apt-get -qq update && \
  apt-get -qq -y install vim

COPY ./nginx/conf.d/default.conf.template /etc/nginx/conf.d/default.conf.template
COPY ./nginx/nginx.conf /etc/nginx/nginx.conf
