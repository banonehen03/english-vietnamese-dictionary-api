FROM php:8.2-apache

# Cài đặt extension SQLite và PDO
RUN apt-get update && apt-get install -y libsqlite3-dev \
    && docker-php-ext-install pdo pdo_sqlite

# Copy toàn bộ code và database vào container
COPY . /var/www/html/

# Mở cổng 80 của Apache
EXPOSE 80