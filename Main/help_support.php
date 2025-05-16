<?php
    session_start();
    $err="";
    if (!isset($_SESSION['username'])) 
    {
        header('Location: /Major_Project/Login/login.php');
        exit();
    }
    else
    {
        $ses_username = $_SESSION['username'];    
        $php_msg = "";

        if ($_SERVER["REQUEST_METHOD"] == "POST") {
            $name = htmlspecialchars($_POST["name"]);
            $email = htmlspecialchars($_POST["email"]);
            $message = htmlspecialchars($_POST["message"]);
            
            if (!preg_match("/^[a-zA-Z ]{2,}$/", $name)) {
                $php_msg = "Invalid name. Only letters and spaces allowed.";
                exit();
            }
            
            if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
                $php_msg = "Invalid email format.";
                exit();
            }
            
            $conn = new mysqli("localhost", "root", "", "binit_db", 3306);
            
            if ($conn->connect_error) {
                $php_msg = "Database connection failed";
                die();
            }
            
            $create_table = "CREATE TABLE IF NOT EXISTS contact_messages (
                id INT AUTO_INCREMENT PRIMARY KEY,
                name VARCHAR(255) NOT NULL,
                email VARCHAR(255) NOT NULL,
                message TEXT NOT NULL,
                created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
            )";
            $conn->query($create_table);
            
            $stmt = $conn->prepare("INSERT INTO contact_messages (name, email, message) VALUES (?, ?, ?)");
            $stmt->bind_param("sss", $name, $email, $message);
            
            if ($stmt->execute()) {
                $php_msg = "Message sent successfully!";
            } else {
                $php_msg = "Error! Couldn't send the message ";
            }
            
            $stmt->close();
            $conn->close();
        }
    }

?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinIt | Help & Support</title>
    <link rel="icon" href="../logo.png" type="image/x-icon">
    <link rel="stylesheet" href="help_support_style.css">
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
    </style>
</head>
<body>
  <nav>
    <div class="nav_left">
        <img src="../logo.png" alt="BinIt Logo" class="logo">
        <p>BinIt</p>
    </div>
    <div class="nav_right">
    <img src="user.png" alt="User-Profile" class="Profile_pic" id="profilePic">
    <div class="username" id="usernameDisplay"><?php echo $ses_username ?></div>
    <div id="profileModal" class="profile-modal">
        <div class="modal-content">
            <ul class="profile-options">
                <li>
                    <a href="#">
                        <img src="user_profile.png" alt="My Profile">
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <img src="change_pass.png" alt="Change Password">
                        <span>Change Password</span>
                    </a>
                </li>
                <li>
                    <a href="logout.php">
                        <img src="logout.png" alt="Log Out">
                        <span>Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
</div>
</div>
</nav>

<div class="sidebar_menu">
    <ul class="main_menu">
        <li>
            <a href="dashboard.php">
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
        <li>
          <a href="analysis_visual.php">
                <i class="fas fa-chart-bar"></i> 
                <span>Analysis & Visualization</span>
            </a>
        </li>
        <!-- <li>
            <a href="survey_feed.html">
                <i class="fas fa-poll"></i> 
                <span style="margin-left: 1vh;">Survey & Feedback</span>
            </a>
        </li> -->
        <li class="active">
            <a href="help_support.php">
                <i class="fas fa-question-circle"></i>
                <span>Help & Support</span>
            </a>
        </li>
    </ul>
</div>

<!-- ----------------------------------------------------------------------------------------------------------------------------- -->
  

<main class="main-content">
  <div class="help-support-container">
    <h2 class="page-title">
      <svg class="icon" viewBox="0 0 24 24" width="32" height="32">
        <path fill="currentColor" d="M12 2C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm1 17h-2v-2h2v2zm2.07-7.75l-.9.92C13.45 12.9 13 13.5 13 15h-2v-.5c0-1.1.45-2.1 1.17-2.83l1.24-1.26c.37-.36.59-.86.59-1.41 0-1.1-.9-2-2-2s-2 .9-2 2H8c0-2.21 1.79-4 4-4s4 1.79 4 4c0 .88-.36 1.68-.93 2.25z"/>
      </svg>
      Help & Support
    </h2>

    <div class="sections-wrapper">
      <section class="faq-section">
      <h3 class="section-title">
        <svg class="icon" viewBox="0 0 24 24" width="20" height="20">
          <path fill="currentColor" d="M11 18h2v-2h-2v2zm1-16C6.48 2 2 6.48 2 12s4.48 10 10 10 10-4.48 10-10S17.52 2 12 2zm0 18c-4.41 0-8-3.59-8-8s3.59-8 8-8 8 3.59 8 8-3.59 8-8 8zm0-14c-2.21 0-4 1.79-4 4h2c0-1.1.9-2 2-2s2 .9 2 2c0 2-3 1.75-3 5h2c0-2.25 3-2.5 3-5 0-2.21-1.79-4-4-4z"/>
        </svg>
        Frequently Asked Questions
      </h3>
      
      <div class="faq-list">
        <details class="faq-item">
          <summary class="faq-question">How do I report an issue with the app?</summary>
          <div class="faq-answer">
            <p>You can report an issue by going to your profile and selecting "Report an issue". Please provide as much detail as possible about the problem you are experiencing.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary class="faq-question">How do I update my account information?</summary>
          <div class="faq-answer">
            <p>Go to your profile settings by clicking on your profile icon in the top right corner. There you can update your name, email address, and other account information.</p>
          </div>
        </details>

        <details class="faq-item">
          <summary class="faq-question">What is the best way to contact support for urgent issues?</summary>
          <div class="faq-answer">
            <p>For urgent issues, you can reach our support team directly through the "Contact Us" form below. We strive to respond to all urgent inquiries within 24 hours.</p>
          </div>
        </details>
      </div>
    </section>
    <section class="contact-section">
      <h3 class="section-title">
        <svg class="icon" viewBox="0 0 24 24" width="20" height="20">
          <path fill="currentColor" d="M20 4H4c-1.1 0-1.99.9-1.99 2L2 18c0 1.1.9 2 2 2h16c1.1 0 2-.9 2-2V6c0-1.1-.9-2-2-2zm0 4l-8 5-8-5V6l8 5 8-5v2z"/>
        </svg>
        Contact Us
      </h3>
      <form id="contact-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
          <input type="text" id="name" name="name" placeholder="Your Name" required />
          <input type="email" id="email" name="email" placeholder="Your Email" required />
          <textarea id="message" name="message" placeholder="Your Message" required></textarea>
        <button type="submit" class="submit-button">Send Message
          <svg class="icon" viewBox="0 0 24 24" width="16" height="16">
            <path fill="currentColor" d="M2.01 21L23 12 2.01 3 2 10l15 2-15 2z"/>
          </svg>
        </button>
        <p class="php_msg"><?php echo $php_msg; ?></p>
      </form>
    </section>
    </div>
  </div>
</main>
    <script>
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
