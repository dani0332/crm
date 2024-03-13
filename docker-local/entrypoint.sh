#!/bin/bash

# Check if the .env file exists
# if [ ! -f /var/www/.env ]; then
    # Run the Doppler command to download secrets and create the .env file
    doppler secrets download --token="${DOPPLER_TOKEN_IMCRM}" --no-file --format="env" > /var/www/.env
# fi

# Start the main process (e.g., php-fpm)
exec "$@"