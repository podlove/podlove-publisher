FROM wordpress:6-php8.1-apache

RUN apt-get update
RUN apt-get install zip default-mysql-client -y

RUN curl -O https://raw.githubusercontent.com/wp-cli/builds/gh-pages/phar/wp-cli.phar
RUN chmod +x wp-cli.phar
RUN mv wp-cli.phar /usr/local/bin/wp

WORKDIR /var/www/html

COPY ./bin/docker-entry.sh /usr/local/bin/entry.sh
COPY ./bin/docker-setup.sh /usr/local/bin/setup.sh
COPY ./dist /tmp/podlove-podcasting-plugin-for-wordpress
RUN cd /tmp/podlove-podcasting-plugin-for-wordpress && zip -r /tmp/podlove-podcasting-plugin-for-wordpress.zip .
RUN rm -rf /tmp/podlove-podcasting-plugin-for-wordpress

ENTRYPOINT ["entry.sh"]
