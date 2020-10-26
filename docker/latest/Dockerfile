FROM php:8.0.0RC2-cli

ENV COMPOSER_CACHE_DIR=/.composer/cache
ENV XDG_CACHE_HOME=/tmp

RUN apt-get update -qq && apt-get install -y -qq libbz2-dev libzip-dev unzip git \
    && docker-php-ext-install bz2 \
    && docker-php-ext-install zip

COPY --from=composer:2 /usr/bin/composer /usr/local/bin/composer

ADD php-config.ini /usr/local/etc/php/conf.d/php-config.ini

WORKDIR /psh