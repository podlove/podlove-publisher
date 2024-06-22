#!/usr/bin/env bash
# Waiting for the MySQL server to start
while ! wp db check --allow-root &>/dev/null; do \
    sleep 0.1; \
done; \

# Running the WordPress installation process
wp core install --allow-root --url=http://127.0.0.1:8080 --path=/var/www/html --title="Podlove Publisher E2E Test Environment" --admin_email=admin@example.com --admin_user=admin --admin_password=admin --skip-email
wp plugin install --activate --allow-root --path=/var/www/html /tmp/podlove-podcasting-plugin-for-wordpress.zip
eval docker-entrypoint.sh "apache2-foreground"
