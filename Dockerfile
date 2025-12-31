FROM php:8.2-fpm
ARG IMCRM_TOKEN
ARG NODE_MAJOR=20

# Set working directory
WORKDIR /var/www

# Add docker php ext repo
ADD https://github.com/mlocati/docker-php-extension-installer/releases/latest/download/install-php-extensions /usr/local/bin/

# Install php extensions
RUN chmod +x /usr/local/bin/install-php-extensions && sync
RUN install-php-extensions mbstring pdo_mysql zip exif pcntl memcached
RUN pecl install redis \
    && docker-php-ext-enable redis
# Install node 21
RUN curl -sL https://deb.nodesource.com/setup_22.x -o /tmp/nodesource_setup.sh
RUN bash /tmp/nodesource_setup.sh
#RUN curl -fsSL https://deb.nodesource.com/gpgkey/nodesource-repo.gpg.key | gpg --dearmor -o /etc/apt/keyrings/nodesource.gpg \
#    && echo "deb [signed-by=/etc/apt/keyrings/nodesource.gpg] https://deb.nodesource.com/node_$NODE_MAJOR.x nodistro main" | tee /etc/apt/sources.list.d/nodesource.list

# Install yarn
#RUN curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | apt-key add - && \
#echo "deb https://dl.yarnpkg.com/debian/ stable main" | tee /etc/apt/sources.list.d/yarn.list

RUN apt-get update && apt-get install -y curl gnupg && \
    mkdir -p /etc/apt/keyrings && \
    curl -sS https://dl.yarnpkg.com/debian/pubkey.gpg | gpg --dearmor | tee /etc/apt/keyrings/yarn.gpg >/dev/null && \
    echo "deb [signed-by=/etc/apt/keyrings/yarn.gpg] https://dl.yarnpkg.com/debian stable main" \
      | tee /etc/apt/sources.list.d/yarn.list

# Install dependencies
RUN apt-get update && apt-get install -y \
    build-essential libssl-dev pkg-config \
    libpng-dev \
    libjpeg-dev \
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
    gnupg \
    supervisor \
    nodejs \
    yarn \
    libwebp-dev \
    qpdf
RUN docker-php-ext-configure gd --enable-gd --with-freetype --with-jpeg --with-webp
RUN docker-php-ext-install -j$(nproc) gd
RUN php -r 'var_dump(function_exists("imagecreatefromwebp"));'
RUN pecl install mongodb-2.1.0 && docker-php-ext-enable mongodb

#RUN (curl -Ls --tlsv1.2 --proto "=https" --retry 3 https://cli.doppler.com/install.sh || wget -t 3 -qO- https://cli.doppler.com/install.sh) | sh
RUN echo "deb [signed-by=/usr/share/keyrings/doppler-archive-keyring.gpg] https://packages.doppler.com/public/cli/deb/debian any-version main" | tee /etc/apt/sources.list.d/doppler-cli.list 
RUN curl -sLf --retry 3 --tlsv1.2 --proto "=https" 'https://packages.doppler.com/public/cli/gpg.DE2A7741A397C129.key' | gpg --dearmor -o /usr/share/keyrings/doppler-archive-keyring.gpg
RUN apt-get update && apt-get install doppler

# Install supervisor
#RUN apt-get install -y supervisor

# Install composer
RUN curl -sS https://getcomposer.org/installer | php -- --install-dir=/usr/local/bin --filename=composer

# Clear cache
RUN apt-get clean && rm -rf /var/lib/apt/lists/*

# Add user for laravel application
RUN groupadd -g 1000 www && \
useradd -u 1000 -ms /bin/bash -g www www

RUN doppler configure set token ${IMCRM_TOKEN}

RUN --mount=type=cache,target=/tmp \
  wget -P /tmp -r -nd --no-parent -A 'newrelic-php5-*-linux.tar.gz' https://download.newrelic.com/php_agent/release/ && \
  cd /tmp/ && tar -zxvf newrelic-php5-*-linux.tar.gz && cd .. && \
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
RUN mkdir /var/log/php && \
touch /var/log/php/errors.log && chmod 777 /var/log/php/errors.log

EXPOSE 80
EXPOSE 443

# Check yarn packages
COPY --chown=www:www-data package*.json yarn.lock /var/www/
RUN yarn install --pure-lockfile

#Check composer packages
#COPY --chown=www:www-data composer*.json composer.lock /var/www/
#RUN composer install --optimize-autoloader --no-dev

COPY --chown=www:www-data . /var/www

# add root to www group
RUN chmod -R ugo+w /var/www/storage

# Copy nginx/php/supervisor configs
RUN cp docker/supervisor.conf /etc/supervisord.conf && \
cp docker/blanka.ini /usr/local/etc/php/conf.d/app.ini && \
# RUN cp docker/info.php /var/www/public/
cp docker/nginx.conf /etc/nginx/sites-enabled/default && \
cp -r docker/*.pem /etc/nginx/conf.d/

# Deployment steps
RUN composer install --optimize-autoloader --no-dev
#RUN yarn
# RUN yarn run prod
RUN chmod +x /var/www/docker/run.sh
# Create log files
#RUN mkdir -p /var/www/storage/logs
#RUN touch /var/www/storage/logs/laravel.log
#RUN chown -R www-data:www-data /var/www/storage
#RUN chmod -R 775 /var/www/storage

ENTRYPOINT ["/var/www/docker/run.sh"]
