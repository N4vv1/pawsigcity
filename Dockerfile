# Use PHP with Apache
FROM php:8.1-apache

# Install dependencies and PHP extensions for PostgreSQL
RUN apt-get update \
    && apt-get install -y libpq-dev \
    && docker-php-ext-install pdo pgsql pdo_pgsql

# Copy project files into container
COPY . /var/www/html/
COPY homepage /var/www/html/homepage
COPY icons /var/www/html/icons


# Expose port 80 for web traffic
EXPOSE 80