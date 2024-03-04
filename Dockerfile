FROM php:8.3.1-apache

RUN docker-php-ext-install mysqli

EXPOSE 80