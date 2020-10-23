FROM php:7.2-apache

COPY . /var/www/html
WORKDIR /var/www/html

RUN apt-get update && apt-get install -y libpng-dev unzip

RUN docker-php-ext-install pdo pdo_mysql mysqli gd mbstring zip

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=1.7.0

RUN a2enmod rewrite
RUN /etc/init.d/apache2 restart

RUN composer install

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri -e 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/*.conf
RUN sed -ri -e 's!/var/www/!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/apache2.conf /etc/apache2/conf-available/*.conf