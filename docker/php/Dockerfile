FROM php:8.2-fpm

RUN apt-get -y update \
    && apt-get install -y libicu-dev \
    && docker-php-ext-configure intl \
    && docker-php-ext-install intl pdo_mysql

COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

RUN install-php-extensions xdebug && \
    rm /usr/local/bin/install-php-extensions && \
    echo "xdebug.mode=develop,debug,coverage" > /usr/local/etc/php/conf.d/extra_conf.ini
