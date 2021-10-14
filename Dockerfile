FROM php:7.4-apache

RUN apt-get update -y && apt-get install -y libpng-dev

RUN docker-php-ext-install gd

EXPOSE 80
WORKDIR /var/www/html
