# Usa l'immagine base PHP con Apache
FROM php:8.2-apache

# Installa l'estensione mysqli (necessaria per connettersi a MySQL)
RUN docker-php-ext-install mysqli && docker-php-ext-enable mysqli

# Installa Composer (necessario per PHPMailer)
COPY --from=composer:latest /usr/bin/composer /usr/bin/composer

# Copia il codice PHP nel container
COPY ./src /var/www/html

# Imposta la directory di lavoro
WORKDIR /var/www/html

# Installa le dipendenze PHP (PHPMailer)
RUN composer install --no-dev --optimize-autoloader