#!/usr/bin/env bash
# Waiting for the MySQL server to start
HOST=$(echo $WORDPRESS_DB_HOST | cut -d: -f1)
PORT=$(echo $WORDPRESS_DB_HOST | cut -d: -f2)

until mysql -h $HOST -P $PORT -D $WORDPRESS_DB_NAME -u $WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -e '\q'; do
  >&2 echo "Mysql is unavailable - sleeping..."
  sleep 2
done

# Running the WordPress installation process
wp core install --allow-root --url=http://127.0.0.1 --path=/var/www/html --title="Podlove Publisher E2E Test Environment" --admin_email=admin@example.com --admin_user=admin --admin_password=admin --skip-email
wp plugin install --activate --allow-root --path=/var/www/html /tmp/podlove-podcasting-plugin-for-wordpress.zip

eval setup.sh
eval docker-entrypoint.sh "apache2-foreground"
