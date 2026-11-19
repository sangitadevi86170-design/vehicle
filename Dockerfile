FROM php:8.2-apache

# Enable rewrite module
RUN a2enmod rewrite

# Support dynamic PORT for Railway / Cloud hosts
RUN sed -i 's/80/${PORT}/g' /etc/apache2/sites-available/000-default.conf /etc/apache2/ports.conf

# Copy API files
COPY api/ /var/www/html/

ENV PORT=80
EXPOSE 80

CMD ["apache2-foreground"]
