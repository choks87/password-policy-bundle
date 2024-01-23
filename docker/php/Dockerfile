FROM php:8.2-cli-bullseye AS base

# SYS: Install required packages
RUN apt-get update
RUN apt-get install --no-install-recommends -y bash
RUN apt-get install --no-install-recommends -y git
RUN apt-get install --no-install-recommends -y sudo
RUN apt-get install --no-install-recommends -y autoconf
RUN apt-get install --no-install-recommends -y gcc
RUN apt-get install --no-install-recommends -y g++
RUN apt-get install --no-install-recommends -y make
RUN apt-get install --no-install-recommends -y gettext
RUN apt-get install --no-install-recommends -y nano
RUN apt-get install --no-install-recommends -y zip
RUN apt-get install --no-install-recommends -y libzip-dev
RUN apt-get install --no-install-recommends -y libxml2-dev

COPY files/bin                    /usr/local/bin/

# PHP Extensions, via docker-php-ext-install and PECL :)
RUN docker-php-ext-install ctype
RUN docker-php-ext-install xml
RUN docker-php-ext-install zip
RUN docker-php-ext-install pdo_mysql
RUN php-ext-enable pdo_mysql

# Install composer
RUN curl -sSk https://getcomposer.org/installer | php -- --disable-tls && \
   mv composer.phar /usr/local/bin/composer

# Setting Working Directory
WORKDIR /app

FROM base AS development

COPY files/ini/20-xdebug.ini      /usr/local/etc/php/available-ini/

# Excensions
RUN pecl install pcov
RUN pecl install xdebug
RUN php-ext-enable pcov
RUN php-ext-enable xdebug
