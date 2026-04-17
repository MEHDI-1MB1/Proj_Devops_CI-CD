FROM php:8.2-apache

# Installation des extensions PHP nécessaires pour le projet
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Activation mod_rewrite
RUN a2enmod rewrite

# Copier tout le code dans le conteneur
COPY . /var/www/html/

# On donne des bonnes permissions pour les uploads
RUN chown -R www-data:www-data /var/www/html && \
    chmod -R 755 /var/www/html/uploads

# On Expose le port 80
EXPOSE 80
