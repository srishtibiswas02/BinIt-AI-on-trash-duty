# Use PHP 8.1 as base image
FROM php:8.1-apache

# Install Python and required packages
RUN apt-get update && apt-get install -y \
    python3 \
    python3-pip \
    libapache2-mod-php \
    php-mysql \
    && rm -rf /var/lib/apt/lists/*

# Set working directory
WORKDIR /var/www/html

# Copy all project files
COPY . .

# Install Python dependencies
RUN pip3 install -r requirements.txt

# Configure Apache for PHP
RUN a2enmod rewrite
RUN service apache2 restart

# Expose ports
EXPOSE 80 5000

# Create startup script
RUN echo '#!/bin/bash\n\
service apache2 start\n\
python3 app.py &\n\
apache2-foreground' > /start.sh && chmod +x /start.sh

# Start both servers
CMD ["/start.sh"]
