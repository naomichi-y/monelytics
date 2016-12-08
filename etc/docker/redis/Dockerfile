FROM redis:3.2.4-alpine
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

COPY ./redis/redis.conf /usr/local/etc/redis/redis.conf
CMD [ "redis-server", "/usr/local/etc/redis/redis.conf" ]
