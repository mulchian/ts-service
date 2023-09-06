FROM php:8.2-fpm-alpine as app

# Useful PHP extension installer image, copy binary into your container
COPY --from=mlocati/php-extension-installer /usr/bin/install-php-extensions /usr/local/bin/

# Install php extensions
# exit on errors, exit on unset variables, print every command as it is executed
RUN set -eux; \
    install-php-extensions pdo pdo_mysql;

# allow super user - set this if you use composer as a
# super user at all times like in docker containers
ENV COMPOSER_ALLOW_SUPERUSER = 1

# obtain composer using multi-stage build
# https://docs.docker.com/develop/develop-images/multistage-build/
COPY --from=composer:2.5.8 /usr/bin/composer /usr/bin/composer

# Here we are copying only the composer files (instead of the entire source)
# right before doing composer install
# This is enough to take advantage of docker cache and composer install will
# be executed only if composer files are changed
# https://medium.com/@softius/faster-docker-builds-with-composer-install-b4d2b15d0fff
COPY ./app/composer.* ./

# install
RUN composer install --prefer-dist --no-dev --no-scripts --no-progress --no-interaction

# copy application files to the working directory
COPY ./app .

# run composer dump-autoload to generate the autoloader
RUN composer dump-autoload --optimize

# Dev image
# This stage is meant to be target-built into a separate image
# https://docs.docker.com/develop/develop-images/multistage-build/#stop-at-a-specific-build-stage
# https://docs.docker.com/compose/compose-file/#target
FROM app as app_dev

# Xdebug has different modes / functionalities. We can default to 'off' and set to 'debug'
# when we run docker compose up if we need it
ENV XDEBUG_MODE=off

# Copy xdebug config file into container
COPY ./php/conf.d/xdebug.ini $PHP_INI_DIR/conf.d/xdebug.ini

# Install xdebug
RUN set -eux; \
	install-php-extensions xdebug
