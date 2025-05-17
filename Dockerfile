# Use PHP 8.1 as base image
FROM php:8.1-apache

# Install Python and required packages
RUN apt-get update && apt-get install -y \
    python3.10 \
    python3.10-venv \
    python3-pip \
    python3.10-dev \
    build-essential \
    libgl1-mesa-glx \
    libglib2.0-0 \
    && rm -rf /var/lib/apt/lists/*

# Install PHP extensions
RUN docker-php-ext-install mysqli pdo pdo_mysql

# Enable Apache modules
RUN a2enmod rewrite

# Set working directory
WORKDIR /var/www/html

# Create and activate Python virtual environment
RUN python3.10 -m venv /opt/venv
ENV PATH="/opt/venv/bin:$PATH"

# Copy all project files
COPY . .

# Install Python dependencies in virtual environment
RUN pip3 install --no-cache-dir -r requirements.txt

# Expose ports
EXPOSE 80 5000

# Create startup script
RUN echo '#!/bin/bash\n\
apache2-foreground &\n\
python3 app.py' > /start.sh && chmod +x /start.sh

# Start both servers
CMD ["/start.sh"]
