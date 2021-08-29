FROM php:7.3.11-fpm-alpine3.10

RUN apk --no-cache add git libzip-dev libxml2-dev mysql-client $PHPIZE_DEPS && \
    pecl install xdebug && \
    docker-php-ext-enable xdebug && \
    docker-php-ext-install zip pdo_mysql mysqli bcmath && \
    rm -rf /var/cache/apk/* /tmp/* /var/tmp/*

RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

RUN mkdir /.config && chmod 777 /.config
RUN mkdir /.composer && chmod 777 /.composer
RUN mv "$PHP_INI_DIR/php.ini-development" "$PHP_INI_DIR/php.ini"

# Xdebug settings
RUN echo "xdebug.mode=develop,debug,trace,profile" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.start_with_request=trigger" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.profiler_output_name=cachegrind.%t" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"
RUN echo "xdebug.output_dir=/app/tests" >> "$PHP_INI_DIR/conf.d/docker-php-ext-xdebug.ini"

WORKDIR /app
