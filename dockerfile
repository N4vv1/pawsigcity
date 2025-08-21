# Use PHP with Apache
FROM php:8.1-apache

# Install PHP extensions for MySQL
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Copy project files into container
COPY . /var/www/html/

# Expose port 80 for web traffic
EXPOSE 80
