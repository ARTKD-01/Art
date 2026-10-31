FROM php:8.3-apache

WORKDIR /var/www/html
COPY . /var/www/html/
RUN a2enmod rewrite headers \
    && printf '%s\n' '<Directory /var/www/html/public>' '    AllowOverride All' '    Require all granted' '</Directory>' > /etc/apache2/conf-available/gangdesk.conf \
    && a2enconf gangdesk

ENV APACHE_DOCUMENT_ROOT=/var/www/html/public
RUN sed -ri 's!/var/www/html!${APACHE_DOCUMENT_ROOT}!g' /etc/apache2/sites-available/000-default.conf /etc/apache2/apache2.conf
EXPOSE 3000
CMD ["bash", "-lc", "sed -i "s/Listen 80/Listen ${PORT:-3000}/" /etc/apache2/ports.conf /etc/apache2/sites-available/000-default.conf && apache2-foreground"]
