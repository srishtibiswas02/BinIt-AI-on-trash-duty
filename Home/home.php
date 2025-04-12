<?php
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
?>

<!DOCTYPE html>
<html lang="en">

<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <link rel="stylesheet" href="home.css">
    <title>Home</title>
</head>

<body>

    <div class="preloader">
        <h1>BinIt</h1>
        <p>AI on Trash Duty</p>
        <div class="grey_bg"></div>
        <div class="green_bg">
            <img src="garbage_truck_pic.png" alt="Garbage Truck">
        </div>
    </div>

    <div class="content">

        <header>
            <nav>
                <div class="logo">
                    <!-- <img src="logo-removebg-preview.png" alt="logo"> -->
                    <p>BinIt</p>
                </div>
                <ul class="nav-links">
                    <li><a href="#about_us">About BinIt</a></li>
                    <li><a href="#features">Features</a></li>
                    <li><a href="#impact">Impact</a></li>
                    <li><a href="#faqs">FAQ</a></li>
                    <li><a href="#contact">Contact Us</a></li>
                    <li><a href="/Major_Project/Login/login.php">Login/Signup</a></li>
                </ul>
            </nav>
        </header>

        <section class="hero">
            <div class="hero_right">
                <img src="eco_bot.png" alt="img">
            </div>
            <div class="hero_left">
                <h1>Detect! Track! Report!</h1>
                <p class="p1">Revolutionizing Waste Management with AI</p>
                <a href="/Major_Project/Login/login.php" class="btn">Get Started Now!</a>
            </div>
            <div class="circle1"></div>
            <div class="circle2"></div>
            <div class="square1"></div>
            <div class="square2"></div>
        </section>

        <section id="about_us">
            <div class="images">
                <img src="man.jpg" alt="Man" class="man">
                <img src="garbage.png" alt="Garbage" class="garbage">
            </div>
            <div class="text">
                <h1>About BinIt</h1>
                <p>BinIt is an AI-powered platform that detects and classifies waste in real-time, helping communities
                    manage waste efficiently. Using computer vision and GPS tracking, it identifies waste locations and
                    alerts authorities for timely cleanup. The platform also categorizes waste for better segregation
                    and disposal. With a user-friendly interface, BinIt encourages community participation, making waste
                    management smarter and more sustainable.
                </p>
            </div>
        </section>


        <section id="features" class="features">
            <h1>Features of BinIt</h1>
            <div class="feature-cards">
                <div class="card">
                    <div class="feature_icons"><img src="recycle-bin.png" alt="Loaction"></div>
                    <h3>Smart Waste Detection</h3>
                    <p>Automatically detects waste in images and videos and classifies it into categories like paper,
                        plastic, metal, and more.</p>
                </div>
                <div class="card">
                    <div class="feature_icons"><img src="locations.png" alt="Loaction"></div>
                    <h3>Real-Time GPS Tracking</h3>
                    <p>Track waste locations in real-time and map them for efficient waste collection and disposal.</p>
                </div>
                <div class="card">
                    <div class="feature_icons"><img src="spam.png" alt="Loaction"></div>
                    <h3>Automated Alerts</h3>
                    <p>Weekly notifications sent to government authorities and NGO's for prioritized cleanup.</p>
                </div>
                <div class="card">
                    <div class="feature_icons"><img src="people.png" alt="Loaction"></div>
                    <h3>Community Participation</h3>
                    <p>Citizens can upload images and videos to report waste and contribute to cleaner surroundings.</p>
                </div>
                <div class="card">
                    <div class="feature_icons"><img src="actionable.png" alt="Loaction"></div>
                    <h3>Data-Driven Insights</h3>
                    <p>Gain valuable insights into waste patterns and optimize waste management strategies.</p>
                </div>
            </div>
        </section>

        <section id="impact">
            <h1>Impact of BinIt</h1>
            <ul class="container">
                <li class="item">
                    <img src="impact1.png" alt="">
                    <p class="text">
                        <strong>Cleaner Cities</strong><br> Reduced illegal dumping and littering of waste, contributes to cleaner environments.
                    </p>
                </li>
                <li class="item">
                    <img src="impact2.png" alt="">
                    <p class="text">
                        <strong>Efficient Waste Pickup</strong><br> Optimized routes for waste collection trucks, saving time and fuel.
                    </p>
                </li>
                <li class="item">
                    <img src="impact3.png" alt="">
                    <p class="text">
                        <strong>Increased Recycling</strong><br> Improved waste segregation practices lead to higher recycling rates.
                    </p>
                </li>
                <li class="item">
                    <img src="impact4.png" alt="">
                    <p class="text">
                        <strong>Healthier Communities</strong><br> Reduced pollution and disease outbreaks from unmanaged waste.
                    </p>
                </li>
                <li class="item">
                    <img src="impact5.png" alt="">
                    <p class="text">
                        <strong>Data-Backed Decisions</strong><br> Authorities can make informed decisions using BinIt’s analytics.
                    </p>
                </li>
            </ul>
        </section>
        
        <section id="faqs">
            <h1>Frequently Asked Questions</h1>
            <ul>
                <li>
                    <p><strong>Q: How does BinIt detect waste?</strong></p>
                    <p>A: BinIt uses AI-powered computer vision to analyze images and videos, detecting and classifying waste in real-time.</p>
                </li>
                <li>
                    <p><strong>Q: Can I use BinIt as an individual?</strong></p>
                    <p>A: Yes! BinIt’s app allows anyone to report waste and contribute to cleaner communities.</p>
                </li>
                <li>
                    <p><strong>Q: Is BinIt available in my city?</strong></p>
                    <p>A: BinIt is expanding rapidly. Check our website or app to see if your city is covered.</p>
                </li>
                <li>
                    <p><strong>Q: How can I partner with BinIt?</strong></p>
                    <p>A: Contact us through the website, and our team will get in touch with you.</p>
                </li>   
            </ul>
        </section>

        <section id="contact">
            <h1>Contact Us</h1>
            <div class="contact_sections">
                <div class="contact_left">
                    <p>Have questions or want to collaborate? Reach out to us!</p>
                    <p>Email: <a href="mailto:info@binit.com">info@binit.com</a></p>
                    <p>Phone: <a href="tel:+18002464866">+1-800-BIN-IT-NOW</a></p>
                </div>
                <form id="contact-form" action="<?php echo htmlspecialchars($_SERVER["PHP_SELF"]); ?>" method="post">
                    <input type="text" name="name" placeholder="Your Name" required>
                    <input type="email" name="email" placeholder="Your Email" required>
                    <textarea name="message" placeholder="Your Message" required></textarea>
                    <button type="submit">Submit</button>
                    <p class="php_msg"><?php echo $php_msg; ?></p>
                </form>
            </div>
        </section>
    
        <footer>
            <p>&copy; 2025 BinIt. All Rights Reserved.</p>
        </footer>
    </div>



    <script>
        setTimeout(() => {
            document.querySelector(".preloader").style.display = "none";
            document.getElementById("content").style.display = "block";
        }, 1200);

        window.addEventListener("scroll", function () {
            const navbar = document.querySelector("nav");
            const links = document.querySelectorAll('nav ul li a');
            const aboutUs = document.querySelector("#about_us");

            if (window.scrollY >= aboutUs.offsetTop - navbar.offsetHeight) {
                navbar.style.backgroundColor = "#40b3a2";
                navbar.style.color = "white";
                links.forEach(link => {
                    link.style.color = "white";
                });
            }
            else {
                navbar.style.backgroundColor = "white";
                navbar.style.color = "#40b3a2";
                links.forEach(link => {
                    link.style.color = "#40b3a2";
                });
            }
        });

    </script>

</body>
</html