FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Install PDO MySQL extension
RUN docker-php-ext-install pdo pdo_mysql

# Allow .htaccess overrides
RUN sed -i 's/AllowOverride None/AllowOverride All/g' /etc/apache2/apache2.conf

# Copy source
COPY src/ /var/www/html/

# Permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80
