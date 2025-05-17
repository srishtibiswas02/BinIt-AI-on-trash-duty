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
    python3-venv \
    libgl1-mesa-glx \
    libglib2.0-0 \
    dnsutils \
    iputils-ping \
    net-tools \
    && docker-php-ext-configure gd --with-freetype --with-jpeg \
    && docker-php-ext-install -j$(nproc) gd mysqli pdo pdo_mysql zip

# Set working directory
WORKDIR /var/www/html

# Create and activate Python virtual environment
RUN python3 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Copy PHP files
COPY . .

# Install Python dependencies in virtual environment
RUN pip3 install --no-cache-dir -r requirements.txt

# Expose ports
EXPOSE 80
EXPOSE 5000

# Create start script with network diagnostics
RUN echo '#!/bin/bash\n\
echo "Testing network connectivity..."\n\
nslookup sql207.infinityfree.com\n\
ping -c 4 sql207.infinityfree.com\n\
echo "Starting services..."\n\
apache2-foreground &\n\
/opt/venv/bin/python3 app.py' > /start.sh && chmod +x /start.sh

# Start Apache and Python app
CMD ["/start.sh"]
