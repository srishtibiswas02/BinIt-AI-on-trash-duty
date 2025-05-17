 # Use an official Python image
FROM python:3.10

# Install PHP and required PHP extensions
RUN apt-get update && apt-get install -y php php-cli php-mbstring php-mysql

# Set workdir
WORKDIR /app

# Copy all files
COPY . .

# Install Python dependencies
RUN pip install -r requirements.txt

# Expose ports for PHP and Flask
EXPOSE 8000 5000

# Create startup script
RUN echo '#!/bin/bash\nphp -S 0.0.0.0:8000 & gunicorn app:app -b 0.0.0.0:5000' > /app/start.sh && chmod +x /app/start.sh

# Start both servers
CMD ["/app/start.sh"]
