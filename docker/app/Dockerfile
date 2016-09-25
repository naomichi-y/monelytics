FROM alpine:3.4
MAINTAINER Naomichi Yamakita

RUN mkdir -p /var/www/monelytics

RUN adduser -D -s /bin/bash -u 1000 webapp
RUN chown -R webapp:webapp /var/www/monelytics
RUN chmod -R 700 /var/www/monelytics

CMD ["true"]
