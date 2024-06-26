#!/usr/bin/env bash
# Waiting for the MySQL server to start
HOST=$(echo $WORDPRESS_DB_HOST | cut -d: -f1)
PORT=$(echo $WORDPRESS_DB_HOST | cut -d: -f2)

until mysql -h $HOST -P $PORT -D $WORDPRESS_DB_NAME -u $WORDPRESS_DB_USER -p$WORDPRESS_DB_PASSWORD -e '\q'; do
  >&2 echo "Mysql is unavailable - sleeping..."
  sleep 2
done

eval setup.sh
eval docker-entrypoint.sh "apache2-foreground"
