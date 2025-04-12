# BinIt: AI on Trash Duty

BinIt is an intelligent waste management system that uses computer vision to identify and categorize trash items, helping users make informed decisions about waste disposal.

## Features

- **Image Recognition**: Upload photos of waste items for AI-powered classification
- **Interactive Map**: View waste hotspots on an interactive map
- **User Authentication**: Secure login and user profile management
- **Analytics Dashboard**: Visualize waste distribution and classification data

## Technology Stack

- **Frontend**: HTML, CSS, JavaScript, PHP
- **Backend**: Python (Flask)
- **Machine Learning**: Roboflow for object detection and classification
- **Database**: MySQL
- **Visualization**: Matplotlib, Supervision for bounding box visualization

## Setup Instructions

### Prerequisites
- Python 3.7+
- XAMPP (for PHP and MySQL)
- Required Python packages (see requirements.txt)

### Installation

1. Clone the repository:
   ```
   git clone https://github.com/srishtibiswas02/BinIt-AI-on-trash-duty.git
   ```

2. Create a virtual environment:
   ```
   python -m venv venv
   source venv/bin/activate  # On Windows: venv\Scripts\activate
   ```

3. Install Python dependencies:
   ```
   pip install -r requirements.txt
   ```

4. Set up the database:
   - Start XAMPP and ensure MySQL is running
   - Create a database named `binit_db`

5. Run the Flask application:
   ```
   python app.py
   ```

6. Access the application at:
   ```
   http://localhost/Major_Project/templates/user_input.php
   ```

## Project Structure

- `app.py`: Main Flask application
- `templates/`: PHP frontend files
- `static/`: CSS, JavaScript, and image assets
- `uploads/`: Temporary storage for uploaded images
- `Login/`: Authentication related files 