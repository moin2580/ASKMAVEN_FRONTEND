# Use official PHP with Apache
FROM php:8.1-apache

# Enable mod_rewrite for clean URLs
RUN a2enmod rewrite
RUN docker-php-ext-install pdo pdo_mysql
# Copy your application files
WORKDIR /var/www/html
COPY AskMaven/ ./

# Set proper permissionsW
RUN chown -R www-data:www-data /var/www/html

# Expose port 80
EXPOSE 80

# Start Apache
CMD ["apache2-foreground"]
