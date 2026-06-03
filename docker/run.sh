#!/bin/bash

cd /var/www

APP_USER="${USER:-www}"

run_as_root() {
    if [[ "$(id -u)" -eq 0 ]]; then
        "$@"
    else
        sudo "$@"
    fi
}

run_as_root chown -R "${APP_USER}:www-data" bootstrap/cache

run_as_root mkdir -p \
    storage/framework/sessions \
    storage/framework/views \
    storage/framework/cache \
    storage/app/public \
    storage/logs

run_as_root chown -R "${APP_USER}:www-data" storage/framework storage/app

run_as_root chown "${APP_USER}:www-data" storage/logs 2>/dev/null || true
run_as_root chmod 2775 storage/logs 2>/dev/null || true
shopt -s nullglob
for f in storage/logs/*.log storage/logs/*.log.*; do
    [ -e "$f" ] || continue
    run_as_root chown "${APP_USER}:www-data" "$f" 2>/dev/null || true
    run_as_root chmod 664 "$f" 2>/dev/null || true
done
shopt -u nullglob

shopt -s nullglob
for path in storage/*; do
    case "$(basename "${path}")" in
        framework | app | logs)
            continue
            ;;
    esac
    run_as_root chown -R "${APP_USER}:www-data" "${path}" 2>/dev/null || true
done
shopt -u nullglob

run_as_root chmod -R 775 storage/framework storage/app bootstrap/cache 2>/dev/null || true
find storage/framework storage/app bootstrap/cache -type d -exec chmod g+s {} + 2>/dev/null || true
run_as_root chmod 2775 storage/logs 2>/dev/null || true

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
