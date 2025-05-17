 #!/bin/bash
php -S 0.0.0.0:8000 &
gunicorn app:app -b 0.0.0.0:5000
