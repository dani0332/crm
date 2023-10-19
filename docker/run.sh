#!/bin/bash

cd /var/www

sudo chown -R $USER:www-data storage
sudo chown -R $USER:www-data bootstrap/cache
chmod -R 775 storage
chmod -R 775 bootstrap/cache

#yes | doppler run -- php artisan horizon:terminate #terminates so its restarted by supervisor
# php artisan migrate:fresh --seed
php artisan config:clear
php artisan route:clear
php artisan view:clear
#php artisan view:cache
#php artisan route:cache
#php artisan queue:restart

yes | doppler run -- php artisan db:seed

doppler run -- /usr/bin/supervisord -c /etc/supervisord.conf
tail -f /dev/null
