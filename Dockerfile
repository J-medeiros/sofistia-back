# Usa imagem oficial do PHP com Apache
FROM php:8.2-apache

# Instala dependências do sistema para compilar extensões
RUN apt-get update && apt-get install -y \
    libpq-dev \
    && docker-php-ext-install pdo pdo_pgsql

# Habilita mod_rewrite do Apache
RUN a2enmod rewrite

# Copia todos os arquivos do projeto para a pasta do Apache
COPY . /var/www/html/

# Define o diretório de trabalho
WORKDIR /var/www/html

# Ajusta permissões
RUN chown -R www-data:www-data /var/www/html \
    && chmod -R 755 /var/www/html

# Instala Composer
COPY --from=composer:2 /usr/bin/composer /usr/bin/composer
RUN composer install --no-dev --optimize-autoloader

# Expõe a porta usada pelo Apache
EXPOSE 80
