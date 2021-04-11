FROM php:7-alpine
RUN apk add --no-cache git make bash

COPY --from=node:14-alpine . .
COPY --from=composer:1 /usr/bin/composer /usr/bin/composer