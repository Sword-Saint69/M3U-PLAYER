FROM php:8.2-apache

# Enable Apache mod_rewrite
RUN a2enmod rewrite headers

# Install useful PHP extensions
RUN docker-php-ext-install opcache

# PHP config: allow large M3U uploads, tune for streaming
RUN echo "upload_max_filesize = 50M"       >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "post_max_size = 50M"             >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "max_execution_time = 300"        >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "output_buffering = Off"          >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "zlib.output_compression = Off"  >> /usr/local/etc/php/conf.d/custom.ini \
 && echo "implicit_flush = On"             >> /usr/local/etc/php/conf.d/custom.ini

# Apache VirtualHost — disable output buffering for streaming
RUN echo '<VirtualHost *:80>\n\
    DocumentRoot /var/www/html/public\n\
    <Directory /var/www/html/public>\n\
        AllowOverride All\n\
        Require all granted\n\
    </Directory>\n\
    # Disable Apache output buffering for live stream proxy\n\
    SetEnv no-gzip 1\n\
    Header unset ETag\n\
    Header set Cache-Control "no-cache, no-store, must-revalidate"\n\
</VirtualHost>' > /etc/apache2/sites-available/000-default.conf

# Copy source code
COPY . /var/www/html/

# Fix permissions
RUN chown -R www-data:www-data /var/www/html

EXPOSE 80

CMD ["apache2-foreground"]
