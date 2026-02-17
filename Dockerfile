FROM php:8.2-apache

# Installa estensioni PHP necessarie
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli
RUN docker-php-ext-install pdo pdo_mysql

# Installa Composer
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Imposta directory di lavoro
WORKDIR /var/www/html

# Copia tutto il codice sorgente (incluso composer.json)
COPY ./src /var/www/html

# Installa dipendenze PHP - vendor finisce in /var/www/html/vendor
RUN composer install --no-dev --optimize-autoloader --working-dir=/var/www/html

# Permessi corretti per Apache
RUN chown -R www-data:www-data /var/www/html

# Abilita mod_rewrite
RUN a2enmod rewrite