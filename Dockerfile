FROM composer:2.2.22 AS composer

FROM php:7.3.11-fpm-alpine3.10

RUN apk add --no-cache --virtual .build-deps $PHPIZE_DEPS \
    && pecl install xdebug-3.0.4 \
    && docker-php-ext-enable xdebug \
    && apk del -f .build-deps

# Composer settings
RUN mkdir /.config && chmod 777 /.config
RUN mkdir /.composer && chmod 777 /.composer
COPY --from=composer /usr/bin/composer /usr/bin/composer

# PHP settings
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Xdebug settings
RUN echo "xdebug.mode=develop,debug,trace,profile" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.start_with_request=trigger" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.profiler_output_name=cachegrind.%t" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.output_dir=/app/tests" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

WORKDIR /app
