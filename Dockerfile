FROM php:8.2-apache

# Enable mod_rewrite
RUN a2enmod rewrite

# Copy files
COPY api/ /var/www/html/

# Expose port 80
EXPOSE 80

CMD ["apache2-foreground"]
