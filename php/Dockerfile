FROM php:8.2-fpm

# Install packages
RUN apt-get update && \
  apt-get install -y \
    curl \
    unzip \
    git \
    && \
  apt-get clean -y && \
  rm -rf /var/cache/apt /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Install Composer
RUN curl -sS https://getcomposer.org/installer \
  | php -- --install-dir=/usr/bin --filename=composer

# Set workdir
WORKDIR /app
