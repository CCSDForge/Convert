#!/bin/sh

uid=$(stat -c %u /srv/app)
gid=$(stat -c %g /srv/app)

if [ "$(id -u)" -eq 0 ] && [ "$(id -g)" -eq 0 ]; then
    if [ $# -eq 0 ]; then
        php-fpm --allow-to-run-as-root
    else
        exec "$@"
    fi
fi
