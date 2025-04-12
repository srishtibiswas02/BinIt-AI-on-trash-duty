<?php
  // Database connection details
  $servername = "localhost";
  $username = "root";
  $password = "";
  $dbname = "binit_db";

  // Create connection
  $conn = new mysqli($servername, $username, $password, $dbname);

  // Check connection
  if ($conn->connect_error) {
    die("Connection failed: " . $conn->connect_error);
  }

  // Fetch data from the database
  $sql = "SELECT latitude, longitude, area, username FROM user_input_tb"; // Make sure 'username' is the actual column name
  $result = $conn->query($sql);

  $data = array();
  if ($result->num_rows > 0) {
    while($row = $result->fetch_assoc()) {
      $data[] = $row;
    }
  }

  // Close connection
  $conn->close();

  // Return data as JSON
//   header('Content-Type: application/json');
//   echo json_encode($data);
?>



<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinIt | Analysis</title>
    <link rel="icon" href="/Major_Project/static/logo.png" type="image/x-icon">
    <link rel="stylesheet" href="/Major_Project/static/analysis_visual_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <link rel="stylesheet" href="https://unpkg.com/leaflet/dist/leaflet.css" /> 
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
    <script src="https://unpkg.com/leaflet/dist/leaflet.js"></script>
</head>
<body>
    <nav>
        <div class="nav_left">
            <img src="/Major_Project/static/logo.png" alt="BinIt Logo" class="logo">
            <p>BinIt</p>
        </div>
        <div class="nav_right">
            <img src="/Major_Project/static/user.png" alt="User-Profile" class="Profile_pic" id="profilePic">
            <div class="username" id="username">USER NAME
                <ul class="profile_card" id="profileMenu">
                    <li>
                        <a href="#">
                            <img src="/Major_Project/static/user_profile.png" alt="My Profile">
                            <span>My Profile</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <img src="/Major_Project/static/change_pass.png" alt="Change Password">
                            <span>Change Password</span>
                        </a>
                    </li>
                    <li>
                        <a href="#">
                            <img src="/Major_Project/static/logout.png" alt="Log Out">
                            <span>Log Out</span>
                        </a>
                    </li>
                </ul>
            </div>
        </div>
    </nav>
    
    <div class="sidebar_menu">
        <ul class="main_menu">
            <li>
                <a href="/Major_Project/Main/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="/Major_Project/templates/user_input.php">
                    <i class="fas fa-keyboard"></i> 
                    <span>User Input</span>
                </a>
            </li>
            <li class="active">
                <a href="/Major_Project/templates/analysis_visual.php">
                    <i class="fas fa-chart-bar"></i> 
                    <span>Analysis & Visualization</span>
                </a>
            </li>
            <li>
                <a href="/Major_Project/Main/survey_feed.html">
                    <i class="fas fa-poll"></i> 
                    <span style="margin-left: 1vh;">Survey & Feedback</span>
                </a>
            </li>
            <li>
                <a href="/Major_Project/Main/help_support.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </li>
        </ul>
    </div>
  
<!-- ----------------------------------------------------------------------------------------------------------------------------- -->

<div class="main-content">
    <?php
    $image_filename = isset($_GET['image']) ? $_GET['image'] : '';
    $has_prediction = false;
    $prediction_data = null;
    
    if (!empty($image_filename)) {
        // Try to fetch prediction data from Flask API
        $api_url = "http://localhost:5000/get_prediction/" . urlencode($image_filename);
        $response = @file_get_contents($api_url);
        
        if ($response !== false) {
            $prediction_data = json_decode($response, true);
            $has_prediction = true;
        }
    }
    ?>
    
    <?php if ($has_prediction): ?>
    <div class="predictions-container">
        <h2>Waste Detection Results</h2>
        <div class="visualizations">
            <div class="visualization-box">
                <h3>Detected Objects</h3>
                <img src="<?php echo $prediction_data['visualization2']; ?>" alt="Bounding Box Visualization">
            </div>
            <div class="visualization-box">
                <h3>Prediction Confidence</h3>
                <img src="<?php echo $prediction_data['visualization1']; ?>" alt="Confidence Chart">
            </div>
        </div>
        
        <div class="prediction-details">
            <h3>Detected Waste Types</h3>
            <ul>
            <?php foreach ($prediction_data['predictions'] as $pred): ?>
                <li>
                    <strong><?php echo htmlspecialchars($pred['class']); ?></strong>: 
                    <?php echo round($pred['confidence'] * 100, 2); ?>% confidence
                </li>
            <?php endforeach; ?>
            </ul>
        </div>
    </div>
    <?php endif; ?>
    
    <h2>Waste Locations Map</h2>
    <div id="map"></div>
</div>

<style>
.predictions-container {
    margin-bottom: 30px;
    padding: 15px;
    background-color: #f5f5f5;
    border-radius: 8px;
}

.visualizations {
    display: flex;
    flex-wrap: wrap;
    justify-content: space-between;
    margin-bottom: 20px;
}

.visualization-box {
    flex: 0 0 48%;
    margin-bottom: 15px;
    background-color: white;
    padding: 10px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.visualization-box img {
    max-width: 100%;
    height: auto;
    display: block;
    margin: 0 auto;
}

.prediction-details {
    background-color: white;
    padding: 15px;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}

.prediction-details ul {
    list-style-type: none;
    padding: 0;
}

.prediction-details li {
    padding: 8px 0;
    border-bottom: 1px solid #eee;
}

#map {
    height: 500px;
    width: 100%;
    border-radius: 8px;
    box-shadow: 0 2px 4px rgba(0,0,0,0.1);
}
</style>

<script>
    var map = L.map('map').setView([28.7041, 77.1025], 10); 

    L.tileLayer('https://tile.openstreetmap.org/{z}/{x}/{y}.png', {
        attribution: '&copy; <a href="https://www.openstreetmap.org/copyright">OpenStreetMap</a> contributors'
    }).addTo(map);

    var data = <?php echo json_encode($data); ?>; 

    data.forEach(zone => {
        L.circleMarker([parseFloat(zone.latitude), parseFloat(zone.longitude)], {
            radius: 8, 
            fillColor: "red", 
            color: "#000", 
            weight: 1, 
            opacity: 1, 
            fillOpacity: 0.8 
        })
        .addTo(map)
        .bindPopup(`<h3>${zone.area}</h3><p>Uploaded by: ${zone.username}</p>`) 
        .openPopup(); 
    });
  </script>
</body>
</html>