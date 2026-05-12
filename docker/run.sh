#!/bin/bash

cd /var/www

run_as_root() {
    if [[ "$(id -u)" -eq 0 ]]; then
        "$@"
    else
        sudo "$@"
    fi
}

run_as_root chown -R "${USER}:www-data" bootstrap/cache

run_as_root mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/app/public \
    storage/logs

run_as_root chown -R "${USER}:www-data" storage/framework storage/app

shopt -s nullglob
for path in storage/*; do
    case "$(basename "${path}")" in
        framework | app | logs)
            continue
            ;;
    esac
    run_as_root chown -R "${USER}:www-data" "${path}" 2>/dev/null || true
done
shopt -u nullglob

run_as_root chmod -R 775 storage/framework storage/app bootstrap/cache 2>/dev/null || true
run_as_root chmod -R g+rwX storage/logs 2>/dev/null || true

yes | doppler run -- php artisan horizon:terminate #terminates so its restarted by supervisor
#php artisan migrate:fresh --seed
#php artisan config:clear
#php artisan route:clear
#php artisan view:clear
#php artisan view:cache
#php artisan route:cache
#php artisan queue:restart

yes | doppler run -- php artisan db:seed

doppler run -- /usr/bin/supervisord -c /etc/supervisord.conf
tail -f /dev/null
