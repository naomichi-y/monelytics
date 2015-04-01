FROM centos:latest
MAINTAINER Naomichi Yamakita <n.yamakita@gmail.com>

## system setting
RUN \cp -p /usr/share/zoneinfo/Japan /etc/localtime

## update system
RUN yum -y update
RUN yum -y install wget
RUN yum -y install hostname

## add yum repository
RUN yum -y install epel-release
RUN rpm -ivh http://rpms.famillecollet.com/enterprise/remi-release-7.rpm

## install supervisor
RUN yum install -y supervisor
ADD build/supervisor/supervisord /etc/rc.d/init.d/
RUN chmod 755 /etc/rc.d/init.d/supervisord
RUN chkconfig supervisord on
RUN echo_supervisord_conf > /etc/supervisord.conf
RUN echo "[include]" >> /etc/supervisord.conf
RUN echo "files = supervisord/conf/*.conf" >> /etc/supervisord.conf
RUN mkdir -p  /etc/supervisord/conf/
ADD build/supervisor/service.conf /etc/supervisord/conf/service.conf

## install mysql
RUN wget http://dev.mysql.com/get/mysql-community-release-el7-5.noarch.rpm
RUN rpm -Uvh mysql-community-release-el7-5.noarch.rpm
RUN yum -y --enablerepo=mysql56-community install mysql-server

RUN mkdir -p /opt/mysql
RUN chown mysql:mysql /opt/mysql

ADD build/mysql/my.cnf /etc/
RUN chmod 755 /tmp/init.sh

# MySQLの初期化をデータコンテナに対して行うにはどうすればいいのわかんないよお
#RUN /usr/bin/mysql_install_db
#RUN chown -R mysql:mysql /opt/mysql
#RUN mysqld_safe & \
#  sleep 10s && \
#  echo "GRANT ALL ON *.* TO webapp@'%' IDENTIFIED BY 'webapp' WITH GRANT OPTION; FLUSH PRIVILEGES" | mysql

## install apache
RUN yum -y install httpd
RUN sed -ri 's/DocumentRoot "\/var\/www\/html"/DocumentRoot "\/var\/www\/public"/g' /etc/httpd/conf/httpd.conf
RUN sed -ri 's/AllowOverride None/AllowOverride All/g' /etc/httpd/conf/httpd.conf

## install php
RUN yum install -y --enablerepo=remi-php56 php php-mbstring php-mcrypt php-mysql
RUN sed -ri 's/;date.timezone =/date.timezone = Asia\/Tokyo/g' /etc/php.ini
RUN sed -ri 's/display_errors = Off/display_errors = On/g' /etc/php.ini

## start services
EXPOSE 80 3306
CMD ["/usr/bin/supervisord"]
