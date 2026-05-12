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

if ! run_as_root chown -R "${USER}:www-data" storage/logs 2>/dev/null; then
    echo "run.sh: could not chown storage/logs (common on PVCs or without CAP_CHOWN); ensure volume is writable for ${USER} / www-data"
fi

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

run_as_root chmod -R 775 storage bootstrap/cache 2>/dev/null || true

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
