#!/bin/bash

remote_syslog

cd /var/www
# php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:cache

doppler run -- /usr/bin/supervisord -c /etc/supervisord.conf

