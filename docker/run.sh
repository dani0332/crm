#!/bin/bash

cd /var/www
# php artisan migrate:fresh --seed
php artisan cache:clear
php artisan route:cache
yes | doppler run -- php artisan db:seed

doppler run -- /usr/bin/supervisord -c /etc/supervisord.conf

