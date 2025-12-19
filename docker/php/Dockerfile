FROM nginx:1.18.0
FROM composer:2.8 AS composer
FROM php:8.2.25-fpm

COPY --from=composer /usr/bin/composer /usr/bin/composer

RUN apt-get update \
    && apt-get install -y --no-install-recommends \
    #    docker-cli \
        ca-certificates \
        curl  \
        iproute2 \
        libicu-dev \
        libyaml-0-2 \
        libyaml-dev \
        libzip-dev \
        strace \
        unzip \
        zip

RUN  install -m 0755 -d /etc/apt/keyrings \
     && curl -fsSL https://download.docker.com/linux/debian/gpg -o /etc/apt/keyrings/docker.asc \
     && chmod a+r /etc/apt/keyrings/docker.asc

# Add the repository to Apt sources:  \
RUN printf '%s\n' \
  'Types: deb' \
  'URIs: https://download.docker.com/linux/debian' \
  "Suites: $(. /etc/os-release && echo "$VERSION_CODENAME")" \
  'Components: stable' \
  'Signed-By: /etc/apt/keyrings/docker.asc' \
  > /etc/apt/sources.list.d/docker.sources

RUN apt update\
    && apt install -y --no-install-recommends docker-ce-cli       \
    && apt-get clean \
    && rm -rf /var/lib/apt/lists/*

RUN pecl install apcu xdebug zip pcntl yaml
RUN docker-php-ext-install  opcache sockets intl pdo pdo_mysql zip pcntl
RUN docker-php-ext-enable   opcache sockets intl pdo pdo_mysql  zip pcntl yaml

COPY 20-xdebug.ini /usr/local/etc/php/conf.d/xdebug.ini

COPY entrypoint.sh /usr/local/entrypoint
RUN chmod +x /usr/local/entrypoint

ENTRYPOINT ["/usr/local/entrypoint"]
