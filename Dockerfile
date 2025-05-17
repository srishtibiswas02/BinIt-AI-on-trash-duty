# Use PHP 8.1 as base image
FROM php:8.1-apache

# Install system dependencies
RUN apt-get update && apt-get install -y \
    libfreetype6-dev \
    libjpeg62-turbo-dev \
    libpng-dev \
    libzip-dev \
    python3 \
    python3-pip \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip

# Set working directory
WORKDIR /var/www/html

# Copy PHP files
COPY . .

# Install Python dependencies in virtual environment
RUN pip3 install --no-cache-dir -r requirements.txt

# Expose ports
EXPOSE 80
EXPOSE 5000

# Create start script
RUN echo '#!/bin/bash\napache2-foreground &\npython3 app.py' > /start.sh && chmod +x /start.sh

# Start Apache and Python app
CMD ["/start.sh"]
