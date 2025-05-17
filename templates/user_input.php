<?php 
    session_start(); 
    $php_err = "";
    $php_msg = " ";

    if (!isset($_SESSION['username'])) 
    {
        header('Location: ../Login/login.php');
        exit();
    }
    else
    {
        $ses_username = $_SESSION['username'];       

        // Security headers to prevent attacks
        header("X-Frame-Options: DENY"); // Prevent clickjacking
        header("X-XSS-Protection: 1; mode=block"); // Prevent XSS
        header("X-Content-Type-Options: nosniff"); // Prevent MIME-type sniffing
    
        $servername = getenv('MYSQL_HOST') ?: "sql207.infinityfree.com";
        $username = getenv('MYSQL_USER') ?: "if0_39005718";
        $password = getenv('MYSQL_PASSWORD') ?: "BinIt020804";
        $dbname = getenv('MYSQL_DATABASE') ?: "if0_39005718_binit_db";
    
        $conn = new mysqli($servername, $username, $password, $dbname);
    
        if ($conn->connect_error) {
            $php_err = "Connection failed";
            exit();
        }
    
        $conn->set_charset("utf8mb4");
    
        $sql_create_table = "CREATE TABLE IF NOT EXISTS user_input_tb (
            id INT AUTO_INCREMENT PRIMARY KEY,
            username VARCHAR(50) NOT NULL,
            image_path VARCHAR(255) NOT NULL,
            latitude DECIMAL(10, 8) NOT NULL,
            longitude DECIMAL(11, 8) NOT NULL,
            area VARCHAR(255),
            city VARCHAR(255),
            timestamp TIMESTAMP DEFAULT CURRENT_TIMESTAMP
        )";

        $conn->query($sql_create_table);
    
        if ($_SERVER["REQUEST_METHOD"] == "POST" && isset($_FILES["image"])) 
        {
            $target_dir = "garbage_img/";
    
            // Validate file type (only allow images)
            $allowed_types = ['jpg', 'jpeg', 'png', 'gif'];
            $file_extension = strtolower(pathinfo($_FILES["image"]["name"], PATHINFO_EXTENSION));
    
            if (!in_array($file_extension, $allowed_types)) {
                $php_err = "Invalid file type! Only JPG, PNG, and GIF allowed.";
                exit();
            }

            $latitude = isset($_POST['latitude']) ? floatval($_POST['latitude']) : 0;
            $longitude = isset($_POST['longitude']) ? floatval($_POST['longitude']) : 0;
            $area = isset($_POST['area']) ? $_POST['area'] : 'Unknown';
            $city = isset($_POST['city']) ? $_POST['city'] : 'Unknown';

            $stmt = $conn->prepare("INSERT INTO user_input_tb (username, image_path, latitude, longitude, area, city) VALUES (?, '', ?, ?, ?, ?)");
            $stmt->bind_param("sddss", $ses_username, $latitude, $longitude, $area, $city);
    
            if ($stmt->execute()) 
            {
                $location_id = $stmt->insert_id;
                $stmt->close();

                    // Secure the filename
                    $target_file = $target_dir . $location_id . "." . $file_extension;
            
                    // Move the uploaded file securely
                    if (move_uploaded_file($_FILES["image"]["tmp_name"], $target_file)) {
                        $stmt = $conn->prepare("UPDATE user_input_tb SET image_path=? WHERE id=?");
                        $stmt->bind_param("si", $target_file, $location_id);
                        $stmt->execute();
                        $stmt->close();
            
                        $php_msg = "Image uploaded successfully with ID: $location_id";
                    } else {
                        $php_err =  "Error uploading image.";
                    }
            }
    
            $conn->close();
        }
    }
?>


<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinIt | User Input</title>
    <link rel="icon" href="../logo.png" type="image/x-icon">
    <link rel="stylesheet" href="../static/userinput_style.css">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/5.15.3/css/all.min.css">
    <script src="https://kit.fontawesome.com/your-font-awesome-kit.js" crossorigin="anonymous"></script>
    <style>
        .profile-modal {
            display: none;
            position: fixed;
            top: 70px;
            right: 20px;
            background-color: white;
            border-radius: 10px;
            box-shadow: 0 5px 15px rgba(0, 0, 0, 0.2);
            z-index: 1001;
            width: 220px;
        }

        .profile-modal.visible {
            display: block;
        }

        .profile-options {
            list-style: none;
            padding: 10px;
        }

        .profile-options li {
            padding: 10px;
            border-radius: 5px;
        }

        .profile-options li:hover {
            background-color: #f5f5f5;
        }

        .profile-options li a {
            display: flex;
            align-items: center;
            gap: 10px;
            text-decoration: none;
            color: #333;
        }

        .profile-options li img {
            height: 20px;
            width: 20px;
        }

        /* Styles for location toggle */
        .location-toggle {
            display: flex;
            margin-bottom: 15px;
            background-color: #f0f0f0;
            border-radius: 8px;
            overflow: hidden;
            width: fit-content;
        }

        .toggle-option {
            padding: 8px 15px;
            cursor: pointer;
            border: none;
            background: none;
            transition: background-color 0.3s;
        }

        .toggle-option.active {
            background-color: #4CAF50;
            color: white;
        }

        /* Manual location input styles */
        .manual-location {
            display: none;
            margin-top: 15px;
        }

        .manual-location.active {
            display: block;
        }

        .location-input {
            display: flex;
            flex-direction: column;
            gap: 10px;
            margin-bottom: 15px;
        }

        .location-input label {
            font-weight: 500;
        }

        .location-input input, .location-input select {
            padding: 8px 12px;
            border: 1px solid #ddd;
            border-radius: 5px;
        }

        .location-input .row {
            display: flex;
            gap: 15px;
        }

        .location-input .row .input-group {
            flex: 1;
        }

        /* Auto location styles */
        .auto-location {
            display: block;
        }

        .location-details {
            padding: 10px 15px;
            background-color: #f9f9f9;
            border-radius: 8px;
            box-shadow: 0 2px 5px rgba(0,0,0,0.05);
        }
    </style>
</head>
<body>
    <nav>
        <div class="nav_left">
        <img src="../logo.png" alt="BinIt Logo" class="logo">
            <p>BinIt</p>
        </div>
        <div class="nav_right">
    <img src="../Main/user.png" alt="User-Profile" class="Profile_pic" id="profilePic">
    <div class="username" id="usernameDisplay"><?php echo $ses_username ?></div>
    <div id="profileModal" class="profile-modal">
        <div class="modal-content">
            <ul class="profile-options">
                <li>
                    <a href="#">
                        <img src="../Main/user_profile.png" alt="My Profile">
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <img src="../Main/change_pass.png" alt="Change Password">
                        <span>Change Password</span>
                    </a>
                </li>
                <li>
                    <a href="../Main/logout.php">
                        <img src="../Main/logout.png" alt="Log Out">
                        <span>Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
    </nav>

    <div class="sidebar_menu">
        <ul class="main_menu">
            <li>
                <a href="../Main/dashboard.php">
                    <i class="fas fa-tachometer-alt"></i> 
                    <span>Dashboard</span>
                </a>
            </li>
            <li class="active">
                <a href="/user_input.php">
                    <i class="fas fa-keyboard"></i> 
                    <span>User Input</span>
                </a>
            </li>
            <li>
                <a href="/analysis_visual.php">
                    <i class="fas fa-chart-bar"></i> 
                    <span>Analysis & Visualization</span>
                </a>
            </li>
            <!-- <li>
                <a href="/Major_Project/Main/survey_feed.html">
                    <i class="fas fa-poll"></i> 
                    <span style="margin-left: 1vh;">Survey & Feedback</span>
                </a>
            </li> -->
            <li>
                <a href="../Main/help_support.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </li>
        </ul>
    </div>

<!-- ----------------------------------------------------------------------------------------------------------------------------- -->

    <div class="main-content">
        <div class="location-container">
            <h3>Location Information</h3>
            
            <!-- Location Toggle Buttons -->
            <div class="location-toggle">
                <button type="button" class="toggle-option active" id="autoLocationBtn">Auto Detect</button>
                <button type="button" class="toggle-option" id="manualLocationBtn">Enter Manually</button>
            </div>
            
            <!-- Auto-detected location -->
            <div class="auto-location" id="autoLocation">
                <div class="location-details" id="locationDetails">
                    <div class="location-detail">Detecting your location...</div>
                </div>
            </div>
            
            <!-- Manual location input -->
            <div class="manual-location" id="manualLocation">
                <div class="location-input">
                    <div class="row">
                        <div class="input-group">
                            <label for="manualArea">Area/Locality</label>
                            <input type="text" id="manualArea" placeholder="e.g., Vasant Kunj">
                        </div>
                        <div class="input-group">
                            <label for="manualCity">City</label>
                            <input type="text" id="manualCity" placeholder="e.g., New Delhi">
                        </div>
                    </div>
                    <div class="row">
                        <div class="input-group">
                            <label for="manualLatitude">Latitude (optional)</label>
                            <input type="number" id="manualLatitude" step="0.000001" placeholder="e.g., 28.5456">
                        </div>
                        <div class="input-group">
                            <label for="manualLongitude">Longitude (optional)</label>
                            <input type="number" id="manualLongitude" step="0.000001" placeholder="e.g., 77.1536">
                        </div>
                    </div>
                    <button type="button" id="useCurrentLocation" class="upload-btn" style="width: auto; margin-top: 10px;">
                        <i class="fas fa-location-arrow"></i> Use My Current Location
                    </button>
                </div>
            </div>
        </div>

        <div class="upload-preview-container"> 
            <div class="upload-container">
                <h2>Upload Waste Image</h2>
                <form action="#" method="post" enctype="multipart/form-data" id="uploadForm">
                    <div class="upload-btn-wrapper">
                        <button type="button" class="upload-btn" onclick="document.getElementById('imageUpload').click();">
                            <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2">
                                <path d="M21 15v4a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2v-4"/>
                                <polyline points="17 8 12 3 7 8"/>
                                <line x1="12" y1="3" x2="12" y2="15"/>
                            </svg>
                            Choose Image
                        </button>
                        <input type="file" name="image" id="imageUpload" accept="image/*" required style="display: none;"> 
                        <div class="php_msg"><?php echo isset($php_msg) ? $php_msg : ''; ?></div>
                        <div class="php_err"><?php echo isset($php_err) ? $php_err : ''; ?></div>
                        <div id="uploadStatus" style="margin-top: 10px;"></div>
                    </div>
                    
                    <!-- Hidden fields for location data -->
                    <input type="hidden" name="latitude" id="latitude">
                    <input type="hidden" name="longitude" id="longitude">
                    <input type="hidden" name="area" id="area">
                    <input type="hidden" name="city" id="city">
                    <input type="hidden" name="username" value="<?php echo $ses_username; ?>">
                    <input type="hidden" name="locationMethod" id="locationMethod" value="auto">

                    <div class="preview-container" id="previewContainer" style="display: none;">
                        <div class="image-preview" id="imagePreview">
                            <img id="uploadedImage" src="#" alt="Uploaded Image" style="display: none;">
                        </div>
                        <button id="reportButton" type="submit" class="upload-btn" style="display: none; margin: auto;">Report Image</button>
                    </div>
                </form>
            </div>
        </div>    
    </div>


    <script>
        document.addEventListener("DOMContentLoaded", function () {
            // Location elements
            const locationDetails = document.getElementById("locationDetails");
            const latitudeField = document.getElementById("latitude");
            const longitudeField = document.getElementById("longitude");
            const areaField = document.getElementById("area");
            const cityField = document.getElementById("city");
            const locationMethodField = document.getElementById("locationMethod");
            
            // Toggle elements
            const autoLocationBtn = document.getElementById("autoLocationBtn");
            const manualLocationBtn = document.getElementById("manualLocationBtn");
            const autoLocation = document.getElementById("autoLocation");
            const manualLocation = document.getElementById("manualLocation");
            
            // Manual location inputs
            const manualArea = document.getElementById("manualArea");
            const manualCity = document.getElementById("manualCity");
            const manualLatitude = document.getElementById("manualLatitude");
            const manualLongitude = document.getElementById("manualLongitude");
            const useCurrentLocation = document.getElementById("useCurrentLocation");
            
            // Upload elements
            const uploadInput = document.getElementById("imageUpload");
            const previewContainer = document.getElementById("previewContainer");
            const imagePreview = document.getElementById("uploadedImage");
            const reportButton = document.getElementById("reportButton");
            const uploadForm = document.getElementById("uploadForm");
            const uploadStatus = document.getElementById("uploadStatus");

            // Toggle between auto and manual location
            autoLocationBtn.addEventListener("click", function() {
                autoLocationBtn.classList.add("active");
                manualLocationBtn.classList.remove("active");
                autoLocation.style.display = "block";
                manualLocation.style.display = "none";
                locationMethodField.value = "auto";
                
                // Re-fetch auto location if needed
                if (locationDetails.innerText.includes("Detecting") || 
                    locationDetails.innerText.includes("failed") || 
                    locationDetails.innerText.includes("denied")) {
                    fetchLocation();
                }
            });
            
            manualLocationBtn.addEventListener("click", function() {
                manualLocationBtn.classList.add("active");
                autoLocationBtn.classList.remove("active");
                manualLocation.style.display = "block";
                autoLocation.style.display = "none";
                locationMethodField.value = "manual";
                
                // Update hidden fields with manual values
                updateLocationFromManualInput();
            });
            
            // Update location from manual inputs
            function updateLocationFromManualInput() {
                const area = manualArea.value.trim() || "Unknown";
                const city = manualCity.value.trim() || "Unknown";
                const lat = manualLatitude.value.trim() ? parseFloat(manualLatitude.value) : 0;
                const lng = manualLongitude.value.trim() ? parseFloat(manualLongitude.value) : 0;
                
                areaField.value = area;
                cityField.value = city;
                latitudeField.value = lat;
                longitudeField.value = lng;
            }
            
            // Listen for changes in manual input fields
            manualArea.addEventListener("input", updateLocationFromManualInput);
            manualCity.addEventListener("input", updateLocationFromManualInput);
            manualLatitude.addEventListener("input", updateLocationFromManualInput);
            manualLongitude.addEventListener("input", updateLocationFromManualInput);
            
            // Use current location button in manual mode
            useCurrentLocation.addEventListener("click", function() {
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(
                        (position) => {
                            const latitude = position.coords.latitude;
                            const longitude = position.coords.longitude;
                            
                            // Fill manual input fields with current location
                            manualLatitude.value = latitude.toFixed(6);
                            manualLongitude.value = longitude.toFixed(6);
                            
                            // Try to get address details
                            fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`)
                                .then(response => response.json())
                                .then(data => {
                                    const area = data.address.suburb || data.address.neighbourhood || data.address.road || "N/A";
                                    const city = data.address.city || data.address.town || data.address.state || "Unknown";
                                    
                                    manualArea.value = area;
                                    manualCity.value = city;
                                    
                                    // Update hidden fields
                                    updateLocationFromManualInput();
                                })
                                .catch(error => {
                                    console.error("Error fetching location details:", error);
                                });
                        },
                        (error) => {
                            console.error("Geolocation error:", error);
                            alert("Failed to get your location. Please enter it manually.");
                        }
                    );
                } else {
                    alert("Geolocation is not supported by your browser. Please enter location manually.");
                }
            });

            async function fetchLocation() {
                if ("geolocation" in navigator) {
                    navigator.geolocation.getCurrentPosition(async (position) => {
                        const latitude = position.coords.latitude;
                        const longitude = position.coords.longitude;

                        latitudeField.value = latitude;
                        longitudeField.value = longitude;

                        try {
                            const response = await fetch(`https://nominatim.openstreetmap.org/reverse?format=json&lat=${latitude}&lon=${longitude}`);
                            const data = await response.json();

                            // const area = data.address.suburb || data.address.neighbourhood || data.address.road || "N/A";
                            const area = "Vasant Kunj";
                            const city = data.address.city || data.address.town || data.address.state || "Unknown";

                            areaField.value = area;
                            cityField.value = city;

                            locationDetails.innerHTML = `
                                <div class="location-detail">📍 ${area}, ${city}</div>
                                <div class="location-detail">📌 Lat: ${latitude.toFixed(4)}</div>
                                <div class="location-detail">📌 Long: ${longitude.toFixed(4)}</div>
                            `;
                        } catch (error) {
                            console.error("Error fetching location details:", error);
                            locationDetails.innerHTML = `<div class="location-detail">⚠️ Location detection failed</div>`;
                        }
                    },
                    (error) => {
                        console.error("Geolocation error:", error);
                        locationDetails.innerHTML = `<div class="location-detail">⚠️ Location access denied</div>`;
                    });
                } else {
                    locationDetails.innerHTML = `<div class="location-detail">⚠️ Geolocation not supported</div>`;
                }
            }

            // Call location fetch function on page load
            fetchLocation();

            // Show image preview when file is selected
            uploadInput.addEventListener("change", function () {
                const file = this.files[0];
                if (file) {
                    const reader = new FileReader();
                    reader.onload = function (e) {
                        imagePreview.src = e.target.result;
                        imagePreview.style.display = "block";
                        previewContainer.style.display = "block";
                        reportButton.style.display = "block";
                    };
                    reader.readAsDataURL(file);
                }
            });

            // Handle form submission
            reportButton.addEventListener("click", function(e) {
                e.preventDefault();
                
                // Make sure location data is updated before submission
                if (locationMethodField.value === "manual") {
                    updateLocationFromManualInput();
                }

                const formData = new FormData(uploadForm);

                fetch("https://binit-ai.onrender.com/process_image", {
                    mode: 'cors',
                    credentials: 'same-origin' 
                })
                .then(response => {
                    return fetch("https://binit-ai.onrender.com/process_image", {
                        method: "POST",
                        body: formData,
                        mode: 'cors',
                        credentials: 'same-origin'
                    });
                })
                .then(response => {
                    return response.json();
                })
                .then(data => {
                    uploadStatus.innerHTML += '<p style="color: green;">Image upload successful!</p>';
                    
                    if (data.success) {
                        uploadStatus.innerHTML += '<p>Redirecting...</p>';
                        window.location.href = data.redirect;
                    }
                })
                .catch(error => {
                    console.error('Fetch Error:', error);
                    uploadStatus.innerHTML += '<p style="color: red;">Error: ' + error.message + '</p>';
                });
            });
        });

        document.addEventListener('DOMContentLoaded', function() {
            const profileModal = document.getElementById('profileModal');
            const usernameDisplay = document.getElementById('usernameDisplay');
            const profilePic = document.getElementById('profilePic');
            
            // Show modal when clicking on username or profile pic
            function toggleModal(event) {
                event.stopPropagation();
                profileModal.classList.toggle('visible');
            }
            
            usernameDisplay.addEventListener('click', toggleModal);
            profilePic.addEventListener('click', toggleModal);
            
            // Close modal when clicking elsewhere
            document.addEventListener('click', function() {
                profileModal.classList.remove('visible');
            });
            
            // Prevent clicks inside modal from closing it
            profileModal.addEventListener('click', function(event) {
                event.stopPropagation();
            });
        });
    </script>

</body>
</html>