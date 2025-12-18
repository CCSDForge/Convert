FROM nginx:1.18.0
FROM composer:2.8 as composer
FROM php:8.2.25-fpm

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
        libzip-dev \
        libicu-dev \
        zip \
        unzip \
        strace iproute2 \
        libyaml-0-2 libyaml-dev \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install apcu xdebug zip pcntl yaml

RUN docker-php-ext-install  opcache sockets intl pdo pdo_mysql zip pcntl
RUN docker-php-ext-enable   opcache sockets intl pdo pdo_mysql  zip pcntl yaml

COPY 20-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

ADD entrypoint.sh /usr/local/entrypoint
RUN chmod +x /usr/local/entrypoint

ENTRYPOINT ["/usr/local/entrypoint"]
