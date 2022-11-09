FROM php:8.0-fpm
ARG IMCRM_TOKEN
#ARG NGINX_FILE
#ARG NEW_RELIC_LICENSE_KEY
#ARG NEW_RELIC_APP_NAME

# Set working directory
WORKDIR /var/www

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync && \
    install-php-extensions mbstring pdo_mysql zip exif pcntl gd memcached

RUN pecl install redis \
    && docker-php-ext-enable redis

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential \
    libpng-dev \
    libjpeg62-turbo-dev \
    libfreetype6-dev \
    locales \
    zip \
    jpegoptim optipng pngquant gifsicle \
    unzip \
    git \
    curl \
    lua-zlib-dev \
    libmemcached-dev \
    nginx \
    wget \
    gnupg
   
# Install node 16
RUN curl -sL https://deb.nodesource.com/setup_16.x -o /tmp/nodesource_setup.sh
RUN bash /tmp/nodesource_setup.sh
RUN apt install nodejs -y

# Install yarn
RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add -
RUN echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list
RUN apt update 
RUN apt install yarn -y

RUN (curl -Ls --tlsv1.2 --proto "=https" --retry 3 https://cli.doppler.com/install.sh || wget -t 3 -qO- https://cli.doppler.com/install.sh) | sh

# Install papertrail
RUN wget https://github.com/papertrail/remote_syslog2/releases/download/v0.20/remote_syslog_linux_amd64.tar.gz
RUN tar xzf ./remote_syslog*.tar.gz
RUN cp /var/www/remote_syslog/remote_syslog /usr/local/bin


# Install supervisor
RUN apt-get install -y supervisor

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add user for laravel application
RUN groupadd -g 1000 www
RUN useradd -u 1000 -ms /bin/bash -g www www
RUN doppler configure set token ${IMCRM_TOKEN}

RUN \
  curl -L https://download.newrelic.com/php_agent/release/newrelic-php5-10.3.0.315-linux.tar.gz | tar -C /tmp -zx && \
  export NR_INSTALL_USE_CP_NOT_LN=1 && \
  export NR_INSTALL_SILENT=1 && \
  /tmp/newrelic-php5-*/newrelic-install install && \
  rm -rf /tmp/newrelic-php5-* /tmp/nrinstall* && \
  sed -i \
      -e 's/"REPLACE_WITH_REAL_KEY"/"${NEW_RELIC_LICENSE_KEY}"/' \
      -e 's/newrelic.appname = "PHP Application"/newrelic.appname = "${NEW_RELIC_APP_NAME}"/' \
      -e 's/;newrelic.daemon.app_connect_timeout =.*/newrelic.daemon.app_connect_timeout=15s/' \
      -e 's/;newrelic.daemon.start_timeout =.*/newrelic.daemon.start_timeout=5s/' \
      /usr/local/etc/php/conf.d/newrelic.ini
# PHP Error Log Files
RUN mkdir /var/log/php
RUN touch /var/log/php/errors.log && chmod 777 /var/log/php/errors.log

EXPOSE 80
EXPOSE 443

# Copy code to /var/www
#ARG CACHEBUST=1
# Check yarn packages
COPY --chown=www:www-data package*.json yarn.lock /var/www
RUN yarn install --pure-lockfile

#Check composer packages
COPY --chown=www:www-data composer*.json composer.lock /var/www
RUN composer install --optimize-autoloader --no-dev

COPY --chown=www:www-data . /var/www

# add root to www group
RUN chmod -R ugo+w /var/www/storage

# Copy nginx/php/supervisor configs
RUN cp docker/supervisor.conf /etc/supervisord.conf
RUN cp docker/blanka.ini /usr/local/etc/php/conf.d/app.ini
RUN cp docker/info.php /var/www/public/
RUN cp docker/nginx.conf /etc/nginx/sites-enabled/default
RUN cp -r docker/*.pem /etc/nginx/conf.d/
RUN cp docker/log_files.yml /etc/

# Deployment steps
#RUN composer install --optimize-autoloader --no-dev
#RUN yarn 
RUN yarn run prod
RUN chmod +x /var/www/docker/run.sh

ENTRYPOINT ["/var/www/docker/run.sh"]
