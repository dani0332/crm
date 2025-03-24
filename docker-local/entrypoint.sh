#!/bin/bash

# download keys and paste them into you .env file
doppler secrets download --token="${DOPPLER_TOKEN_IMCRM}" --no-file --format="env" > /var/www/.env
# Start the main process (e.g., php-fpm)
exec "$@"