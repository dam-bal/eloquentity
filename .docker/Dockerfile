FROM php:8.1-cli

RUN pecl install xdebug && docker-php-ext-enable xdebug

COPY ./.docker/xdebug.ini "${PHP_INI_DIR}/conf.d"

WORKDIR /usr/src/myapp
