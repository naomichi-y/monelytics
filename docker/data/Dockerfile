FROM alpine:3.4
MAINTAINER "Naomichi Yamakita" <n.yamakita@gmail.com>

RUN mkdir -p /data

RUN adduser -D -s /bin/bash -u 1000 webapp
RUN chown -R webapp:webapp /data
RUN chmod -R 700 /data

VOLUME ["/data"]

CMD ["true"]
