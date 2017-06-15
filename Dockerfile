FROM limit0/php56:latest

COPY conf/vhost.conf /etc/apache2/sites-available/000-default.conf
COPY conf/php.ini /usr/local/etc/php/php.ini
COPY . /var/www/html
RUN chown -R www-data:www-data /var/www/html/var && chmod -R 0755 /var/www/html/var
ENV APP_ENV prod
