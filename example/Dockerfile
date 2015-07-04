FROM php:5.6-fpm

MAINTAINER shayashibara <shayashibara@trifort.jp>

LABEL version="1.0"

RUN apt-get update && apt-get install -y  wget unzip php5-dev
RUN docker-php-ext-install mbstring pdo_mysql
RUN docker-php-ext-install opcache

WORKDIR /root
RUN wget https://github.com/nicolasff/phpredis/archive/2.2.7.zip
RUN unzip 2.2.7.zip

WORKDIR phpredis-2.2.7

RUN phpize
RUN ./configure
RUN make && make install

RUN cp /root/phpredis-2.2.7/modules/redis.so /usr/lib/php5/20131226/
RUN mkdir /etc/php5/conf.d
RUN echo "extension=redis.so" > /etc/php5/conf.d/redis.ini
RUN echo "extension=redis.so" > /usr/local/etc/php/conf.d/redis.ini

ADD php-fpm.conf /usr/local/etc/php-fpm.conf

ENV COMPOSER_VERSION 1.0.0-alpha10
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer --version=${COMPOSER_VERSION}

WORKDIR /var/www/html

ADD composer.json ./composer.json
RUN composer --ansi install

ADD index.php ./index.php

CMD ["php-fpm"]
