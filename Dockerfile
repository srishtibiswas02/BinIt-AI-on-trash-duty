# Use PHP 8.1 as base image
FROM php:8.1-apache

# Install Python and required packages
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install Python dependencies
RUN pip3 install -r requirements.txt

# Expose ports
EXPOSE 80 5000

# Create startup script
RUN echo '#!/bin/bash\n\
apache2-foreground &\n\
python3 app.py' > /start.sh && chmod +x /start.sh

# Start both servers
CMD ["/start.sh"]
