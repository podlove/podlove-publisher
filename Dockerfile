FROM wordpress:6-php8.1-apache

RUN apt-get update
RUN apt-get install zip default-mysql-client -y

WORKDIR /var/www/html

COPY ./bin/docker-entry.sh /usr/local/bin/entry.sh
COPY ./bin/docker-setup.sh /usr/local/bin/setup.sh
COPY ./dist wp-content/plugins/podlove-podcasting-plugin-for-wordpress

ENTRYPOINT ["entry.sh"]
