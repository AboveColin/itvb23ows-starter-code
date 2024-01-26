FROM php:8.3.1-apache

RUN docker-php-ext-install mysqli

COPY . /var/www/html

EXPOSE 80