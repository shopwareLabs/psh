FROM php:7.1-cli

ENV COMPOSER_CACHE_DIR=/.composer/cache

ADD php-config.ini /usr/local/etc/php/conf.d/php-config.ini
RUN apt-get update -qq && apt-get install -y -qq libbz2-dev
RUN docker-php-ext-install bz2

RUN pecl install xdebug-2.6.0 \
    && docker-php-ext-enable xdebug

WORKDIR /psh