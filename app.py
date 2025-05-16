from flask import Flask, request, render_template, jsonify, redirect, url_for, send_from_directory
import os
import json
import numpy as np 
import matplotlib
matplotlib.use('Agg')  # Use non-interactive backend to avoid thread issues
import matplotlib.pyplot as plt
from werkzeug.utils import secure_filename
from inference_sdk import InferenceHTTPClient 
import io
import base64
from PIL import Image
import cv2
import supervision as sv
from flask_cors import CORS  # Import CORS
import mysql.connector  #pip install mysql-connector-python
from datetime import datetime
import traceback

app = Flask(__name__, template_folder='templates', static_folder='static')
CORS(app, resources={r"/*": {"origins": ["http://localhost", "http://127.0.0.1"]}}, supports_credentials=True)
app.config['UPLOAD_FOLDER'] = 'uploads'
app.config['VIS_FOLDER'] = 'static/visualizations'
app.config['MAX_CONTENT_LENGTH'] = 16 * 1024 * 1024  # 16MB limit
app.config['ALLOWED_EXTENSIONS'] = {'png', 'jpg', 'jpeg', 'gif'}

# Make sure the upload and visualization folders exist
os.makedirs(app.config['UPLOAD_FOLDER'], exist_ok=True)
os.makedirs(app.config['VIS_FOLDER'], exist_ok=True)

# Initialize the Roboflow client
CLIENT = InferenceHTTPClient(
    api_url="https://detect.roboflow.com",
    api_key="ug33Q66g3g7GZEsiCOOB"
)

def allowed_file(filename):
    return '.' in filename and \
           filename.rsplit('.', 1)[1].lower() in app.config['ALLOWED_EXTENSIONS']

@app.route('/')
def index():
    return redirect('/Major_Project/templates/user_input.php')

# Add a route that catches common paths
@app.route('/login')
def login_redirect():
    return redirect('/Major_Project/Login/login.php')

@app.route('/dashboard')
def dashboard_redirect():
    return redirect('/Major_Project/dashboard.php')

@app.route('/uploads/<filename>')
def uploaded_file(filename):
    return send_from_directory(app.config['UPLOAD_FOLDER'], filename)

db_config = {
    'host': 'localhost',
    'user': 'root',
    'password': '',  # Update this if your MySQL has a password
    'database': 'binit_db'
}

def init_db():
    try:
        print("⏳ Testing MySQL connection...")
        conn = mysql.connector.connect(
            host=db_config['host'],
            user=db_config['user'],
            password=db_config['password'],
            connection_timeout=5  # Reduced for quick fail
        )
        print("✅ Connected to MySQL server")
        
        # Create database if not exists
        cursor = conn.cursor()
        cursor.execute("CREATE DATABASE IF NOT EXISTS binit_db")
        conn.commit()
        print("✅ Database ensured")
        
        # Now connect to the specific database
        conn.close()

        print("🔁 Connecting again with database name...")
        conn = mysql.connector.connect(**db_config)
        print("✅ Connected to binit_db")

        cursor = conn.cursor()
        cursor.execute("""
            CREATE TABLE IF NOT EXISTS user_input_tb (
                id INT AUTO_INCREMENT PRIMARY KEY,
                username VARCHAR(50) NOT NULL,
                image_path VARCHAR(255) NOT NULL,
                latitude DECIMAL(10, 8) NOT NULL,
                longitude DECIMAL(11, 8) NOT NULL,
                area VARCHAR(255),
                city VARCHAR(255),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        print("✅ Table user_input_tb created")

        cursor.execute("""
            CREATE TABLE IF NOT EXISTS garbage_classification_tb (
                id INT AUTO_INCREMENT PRIMARY KEY,
                image_path VARCHAR(255),
                username VARCHAR(50),
                garbage_classification VARCHAR(255),
                timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )
        """)
        print("✅ Table garbage_classification_tb created")

        conn.commit()
        cursor.close()
        conn.close()
        print("✅ Tables created successfully")
        return True

    except mysql.connector.Error as err:
        print(f"❌ MySQL Error: {err}")
        return False
    except Exception as e:
        print(f"❌ General Error: {e}")
        traceback.print_exc()
        return False

def generate_confidence_chart(predictions, filename, classes):
    """Generate a bar chart of prediction confidences and save to a file"""
    if not predictions:
        return ""
    
    # Extract class names and confidences
    confidences = []
    
    for pred in predictions:
        confidences.append(pred.get('confidence', 0) * 100)  # Convert to percentage
    
    # Create the bar chart
    plt.figure(figsize=(10, 6))
    plt.bar(classes, confidences, color='#4ABDAC')
    plt.xlabel('Waste Class')
    plt.ylabel('Confidence (%)')
    plt.title('Prediction Confidence')
    plt.xticks()
    plt.tight_layout()
    
    # Save the plot to a file
    vis_filename = f"conf_chart_{filename.split('.')[0]}.png"
    vis_path = os.path.join(app.config['VIS_FOLDER'], vis_filename)
    plt.savefig(vis_path)
    plt.close()
    
    return f"{app.config['VIS_FOLDER']}/{vis_filename}"

def generate_supervision_bbox_vis(image_path, predictions, filename):
    """Generate a visualization with bounding boxes using supervision library"""
    try:
        # Read the image with OpenCV
        image = cv2.imread(image_path)
        
        # Process the predictions if bbox is present
        for pred in predictions:
            if 'bbox' in pred:
                # Extract the coordinates from bbox
                x_min, y_min, width, height = pred['bbox'].values()
                pred['x'] = x_min + width/2
                pred['y'] = y_min + height/2
                pred['width'] = width
                pred['height'] = height
        
        # Extract class names and create mapping to numeric indices
        class_names = [pred['class'] for pred in predictions]
        class_dict = {name: idx for idx, name in enumerate(set(class_names))}
        class_id_to_name = {idx: name for name, idx in class_dict.items()}
        
        # Create supervision Detections object
        detections = sv.Detections(
            xyxy=np.array([
                [pred['x'] - pred['width']/2, 
                 pred['y'] - pred['height']/2, 
                 pred['x'] + pred['width']/2, 
                 pred['y'] + pred['height']/2] 
                for pred in predictions
            ]),
            confidence=np.array([pred['confidence'] for pred in predictions]),
            class_id=np.array([class_dict[pred['class']] for pred in predictions]),
        )
        
        # Create supervision annotators
        box_annotator = sv.BoxAnnotator()
        label_annotator = sv.LabelAnnotator()
        
        # Annotate the image with bounding boxes and labels
        annotated_image = box_annotator.annotate(scene=image.copy(), detections=detections)
        annotated_image = label_annotator.annotate(
            scene=annotated_image, 
            detections=detections,
            labels=[f"{class_id_to_name[i]}: {confidence:.2f}" for i, confidence in zip(detections.class_id, detections.confidence)]
        )
        
        # Save the annotated image
        vis_filename = f"bbox_{filename.split('.')[0]}.png"
        vis_path = os.path.join(app.config['VIS_FOLDER'], vis_filename)
        cv2.imwrite(vis_path, annotated_image)
        
        return f"{app.config['VIS_FOLDER']}/{vis_filename}"
    
    except Exception as e:
        print(f"Error generating bounding box visualization: {e}")
        return ""

@app.route('/process_image', methods=['POST'])
def process_image():
    try:
        app.logger.debug("Request received at /process_image")
        print("📥 Request received at /process_image") # Add this for console logging
        app.logger.debug(f"Form data: {request.form}")
        app.logger.debug(f"Files: {request.files}")
        
        if 'image' not in request.files:
            return jsonify({'error': 'No file part'}), 400
        
        file = request.files['image']
        
        if file.filename == '':
            return jsonify({'error': 'No selected file'}), 400
        
        # Get location data and username from form
        latitude = request.form.get('latitude', 'Unknown')
        longitude = request.form.get('longitude', 'Unknown')
        area = request.form.get('area', 'Unknown')
        city = request.form.get('city', 'Unknown')
        username = request.form.get('username', 'Unknown')
        
        print(f"📍 Location: {latitude}, {longitude}, {area}, {city}")
        print(f"👤 Username: {username}")
        
        if file and allowed_file(file.filename):
            original_filename = secure_filename(file.filename)
            unique_filename = f"{datetime.now().strftime('%Y%m%d%H%M%S')}_{original_filename}"
            filepath = os.path.join(app.config['UPLOAD_FOLDER'], unique_filename)
            file.save(filepath)
            print(f"💾 File saved to {filepath}")
            
            # Process the image with Roboflow
            try:
                result = CLIENT.infer(filepath, model_id="major-project-zlue6/4")
                classes = [pred['class'] for pred in result['predictions']]
                predictions = result.get('predictions', [])
                
                # Extract primary classification (highest confidence)
                primary_classification = "Unknown"
                if predictions:
                    # Get the prediction with highest confidence
                    primary_classification = max(predictions, key=lambda x: x['confidence'])['class']
                
                # Generate and save visualizations to files
                vis1_path = generate_confidence_chart(predictions, unique_filename, classes)
                vis2_path = generate_supervision_bbox_vis(filepath, predictions, unique_filename)
                
                # Save prediction data for the PHP page to access
                prediction_data = {
                    'predictions': predictions,
                    'visualization1': f"/Major_Project/{vis1_path}",
                    'visualization2': f"/Major_Project/{vis2_path}",
                    'filename': unique_filename,
                    'primary_classification': primary_classification
                }
                
                # Save the result for the PHP to access later
                with open(os.path.join(app.config['UPLOAD_FOLDER'], f"{unique_filename}.json"), 'w') as f:
                    json.dump(prediction_data, f)
                
                # Save to database
                try:
                    conn = mysql.connector.connect(**db_config)
                    cursor = conn.cursor()
                    
                    # Insert into user_input_tb
                    user_input_query = """
                        INSERT INTO user_input_tb (username, image_path, latitude, longitude, area, city)
                        VALUES (%s, %s, %s, %s, %s, %s)
                    """
                    cursor.execute(user_input_query, (username, unique_filename, latitude, longitude, area, city))
                    
                    # Insert into garbage_classification_tb
                    classification_query = """
                        INSERT INTO garbage_classification_tb (username, image_path, garbage_classification)
                        VALUES (%s, %s, %s)
                    """
                    for pred in predictions:
                        detected_class = pred.get('class')
                        cursor.execute(classification_query, (username, unique_filename, detected_class))
                    # cursor.execute(classification_query, (username, unique_filename, primary_classification))
                    
                    conn.commit()
                    cursor.close()
                    conn.close()
                    print("✅ Image data saved to database")
                except mysql.connector.Error as err:
                    print(f"⚠️ Database error: {err}")
                
                return jsonify({
                    'success': True,
                    'message': 'Image processed successfully',
                    'redirect': f'/Major_Project/templates/analysis_visual.php?image={unique_filename}',
                    'visualizations': {
                        'confidence_chart': f"/Major_Project/{vis1_path}",
                        'bounding_box': f"/Major_Project/{vis2_path}"
                    }
                })
                
            except Exception as e:
                print(f"🔴 Roboflow processing error: {e}")
                return jsonify({'error': f"Image processing error: {str(e)}"}), 500
        
        return jsonify({'error': 'Invalid file type'}), 400
            
    except Exception as e:
        print(f"❌ Error in process_image: {e}")
        return jsonify({'error': f"Server error: {str(e)}"}), 500

@app.route('/get_prediction/<filename>', methods=['GET'])
def get_prediction(filename):
    """API endpoint for PHP to get the prediction results"""
    try:
        json_file = os.path.join(app.config['UPLOAD_FOLDER'], f"{filename}.json")
        if os.path.exists(json_file):
            with open(json_file, 'r') as f:
                prediction_data = json.load(f)
            return jsonify(prediction_data)
        else:
            return jsonify({'error': 'Prediction not found'}), 404
    except Exception as e:
        return jsonify({'error': str(e)}), 500

@app.route('/test', methods=['GET'])
def test():
    return jsonify({
        'status': 'success',
        'message': 'Flask server is running correctly!'
    })

if __name__ == '__main__':
    print("🚀 Starting Flask App...")
    db_status = init_db()
    if not db_status:
        print("⚠️ Running with database features disabled")
    app.run(debug=True, port=5000)