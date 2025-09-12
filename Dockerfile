# Use PHP with Apache
FROM php:8.1-apache

# Install PHP extensions for PostgreSQL
RUN docker-php-ext-install pdo pgsql pdo_pgsql

# Copy project files into container
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
