#
# PHP Dependencies
#
FROM composer:latest as vendor

WORKDIR /app/

COPY composer.json composer.json
#COPY composer.lock composer.lock
RUN composer install \
    --ignore-platform-reqs \
    --no-interaction \
    --no-plugins \
    --no-scripts \
    --prefer-dist


#
# Application
#
FROM php:fpm-alpine

RUN docker-php-ext-install pdo_mysql
COPY --from=vendor /app/vendor/ /var/www/vendor/

CMD ["php-fpm"]

EXPOSE 9000