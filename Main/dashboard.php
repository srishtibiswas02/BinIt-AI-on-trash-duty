<?php
    session_start();
    // session_destroy();
    $err="";
    if (!isset($_SESSION['username'])) 
    {
        header('Location: ../Login/login.php');
        exit();
    }
    elseif($_SESSION['username']=="Admin")
    {
        header('Location: ../mail_garbage_report.php');
        exit();
    }
    else
    {
        $ses_username = $_SESSION['username'];       
        
        $servername = "sql207.infinityfree.com";
        $username = "if0_39005718"; 
        $password = "BinIt020804"; 
        $dbname = "if0_39005718_binit_db"; 
    
        $conn = new mysqli($servername, $username, $password, $dbname);
    
        if ($conn->connect_error) {
            $php_err = "Connection failed";
            exit();
        }
    
        $sql = "SELECT timestamp FROM user_input_tb WHERE username = ? ORDER BY timestamp DESC LIMIT 1";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ses_username);
        $stmt->execute();
        $result = $stmt->get_result();
        
        if ($row = $result->fetch_assoc()) {
            $last_upload_time = $row['timestamp'];
            $next_upload_deadline = date("Y-m-d H:i:s", strtotime($last_upload_time . ' +1 day'));
        } else {
            $last_upload_time = null;
            $next_upload_deadline = "N/A"; // No uploads yet
        }
    
        // Calculate the streak
        $sql_streak = "SELECT COUNT(DISTINCT DATE(timestamp)) AS streak FROM user_input_tb 
                       WHERE username = ? AND timestamp >= DATE_SUB(NOW(), INTERVAL 7 DAY)";
        $stmt_streak = $conn->prepare($sql_streak);
        $stmt_streak->bind_param("s", $ses_username);
        $stmt_streak->execute();
        $result_streak = $stmt_streak->get_result();
        
        $streak = 0;
        if ($row_streak = $result_streak->fetch_assoc()) {
            $streak = $row_streak['streak'];
        }
    
        $stmt->close();
        $stmt_streak->close();

        $sql = "SELECT DISTINCT DATE(timestamp) AS streak_date FROM user_input_tb WHERE username = ?";
        $stmt = $conn->prepare($sql);
        $stmt->bind_param("s", $ses_username);
        $stmt->execute();
        $result = $stmt->get_result();

        $streak_dates = [];
        while ($row = $result->fetch_assoc()) {
            $streak_dates[] = $row['streak_date']; // Collect all streak dates
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
    <title>BinIt | Dashboard</title>
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css"> 
    <link rel="stylesheet" href="dashboard_style.css">
    <link rel="icon" href="../logo.png" type="image/x-icon">
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
            <img src="../static/logo.png" alt="BinIt Logo" class="logo">
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
    </nav>

    <div class="sidebar_menu">
        <ul class="main_menu">
            <li class="active">
                <a href="dashboard.php">
                    <i class="fas fa-tachometer-alt"></i>
                    <span>Dashboard</span>
                </a>
            </li>
            <li>
                <a href="../templates/user_input.php">
                    <i class="fas fa-keyboard"></i>
                    <span>User Input</span>
                </a>
            </li>
            <li>
                <a href="../templates/analysis_visual.php">
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
            <li>
                <a href="help_support.php">
                    <i class="fas fa-question-circle"></i>
                    <span>Help & Support</span>
                </a>
            </li>
        </ul>
    </div>

    <div class="main-content">
        <div class="left-panel">
            <div class="welcome">
                <h2>Welcome, <span id="userNameDisplay"><?php echo $ses_username ?></span></h2>
                <p>See how you're contributing to a cleaner environment.</p>
            </div>

            <div class="chart-container">
                <canvas id="myChart"></canvas>
            </div>

            <d<div class="call-to-action">
                <a href="../templates/user_input.php" style="text-decoration: none;">
                    <button><i class="fas fa-trash-alt"></i> Report</button>
                </a>
                <a href="help_support.php" style="text-decoration: none;">
                    <button><i class="fas fa-poll"></i> Help & Support</button>
                </a>
            </div>
        </div>

        <div class="right-panel">
            <div class="recent-activity">
                <h3>Recent Activity</h3>
                <ul>
                    <li>You reported <b>2kg</b> of Plastic waste</li>
                    <?php if ($streak >= 0): ?>
                        <li><b><?php echo $streak; ?>-Day</b> streak achieved! 🔥</li>
                        <li>Upload next Snap before <b><?php echo date("d-m-Y H:i:s", strtotime($last_upload_time . ' +1 day')); ?></b></li>
                    <?php else: ?>
                        <li>No active streak. <b>Start today</b> to build your streak! 🚀</li>
                    <?php endif; ?>
                </ul>
            </div>

            <div class="calendar-container">
                <div class="calendar-header">
                    <button id="prevMonth">&lt;</button>
                    <h2 id="currentMonth"></h2>
                    <button id="nextMonth">&gt;</button>
                </div>
                <div class="calendar-grid" id="calendar"></div>
            </div>
        </div>
    </div>


    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
            // Calendar with streak feature
        const streakData = <?php echo json_encode($streak_dates); ?>;
            class Calendar 
            {
                constructor() 
                {
                    this.date = new Date();
                    this.currentMonth = this.date.getMonth();
                    this.currentYear = this.date.getFullYear();
                    this.monthNames = ["January", "February", "March", "April", "May", "June",
                        "July", "August", "September", "October", "November", "December"
                    ];
                    this.dayNames = ["Sun", "Mon", "Tue", "Wed", "Thu", "Fri", "Sat"];

                    this.streakData = streakData || []; // Streak data from PHP
                    this.initializeCalendar();
                    this.addEventListeners();
                }

                renderCalendar() 
                {
                    const calendar = document.getElementById('calendar');
                    calendar.innerHTML = '';

                    // Add day headers
                    this.dayNames.forEach(day => {
                        const dayHeader = document.createElement('div');
                        dayHeader.className = 'day-header';
                        dayHeader.textContent = day;
                        calendar.appendChild(dayHeader);
                    });

                    // Get first day of month and total days
                    const firstDay = new Date(this.currentYear, this.currentMonth, 1);
                    const lastDay = new Date(this.currentYear, this.currentMonth + 1, 0);
                    const totalDays = lastDay.getDate();
                    const startingDay = firstDay.getDay();

                    // Previous month's days
                    const prevMonthLastDay = new Date(this.currentYear, this.currentMonth, 0).getDate();
                    for (let i = startingDay - 1; i >= 0; i--) {
                        const day = document.createElement('div');
                        day.className = 'day other-month';
                        day.textContent = prevMonthLastDay - i;
                        calendar.appendChild(day);
                    }

                    // Current month's days
                    const today = new Date();
                    for (let i = 1; i <= totalDays; i++) {
                        const day = document.createElement('div');
                        day.className = 'day';

                        // Create date string in YYYY-MM-DD format
                        const dateString = `${this.currentYear}-${String(this.currentMonth + 1).padStart(2, '0')}-${String(i).padStart(2, '0')}`;

                        // Highlight streak days
                        if (this.streakData.includes(dateString)) {
                            day.classList.add('streak-day'); // Add CSS class for styling
                            day.innerHTML = `${i} 🔥`; // Show flame emoji for streak
                        } else {
                            day.textContent = i;
                        }

                        if (this.currentYear === today.getFullYear() &&
                            this.currentMonth === today.getMonth() &&
                            i === today.getDate()) {
                            day.classList.add('today');
                        }

                        calendar.appendChild(day);
                    }

                    // Next month's days
                    const remainingDays = 42 - (startingDay + totalDays);
                    for (let i = 1; i <= remainingDays; i++) {
                        const day = document.createElement('div');
                        day.className = 'day other-month';
                        day.textContent = i;
                        calendar.appendChild(day);
                    }
                }

                addEventListeners() {
                    document.getElementById('prevMonth').addEventListener('click', () => {
                        this.currentMonth--;
                        if (this.currentMonth < 0) {
                            this.currentMonth = 11;
                            this.currentYear--;
                        }
                        this.initializeCalendar();
                    });

                    document.getElementById('nextMonth').addEventListener('click', () => {
                        this.currentMonth++;
                        if (this.currentMonth > 11) {
                            this.currentMonth = 0;
                            this.currentYear++;
                        }
                        this.initializeCalendar();
                    });
                }

                initializeCalendar() {
                    document.getElementById('currentMonth').textContent = this.monthNames[this.currentMonth] + " " + this.currentYear;
                    this.renderCalendar();
                }
            }
        document.addEventListener('DOMContentLoaded', () => {

            // Initialize total waste data
            const totalWasteData = {
                labels: ['Total Waste Collected'],
                datasets: [{
                    label: 'Waste Types (kg)',
                    data: [
                        {
                            x: 'Total Waste',
                            Plastic: 45,
                            Paper: 30,
                            Glass: 15,
                            Organic: 25
                        }
                    ],
                    backgroundColor: [
                        'rgba(255, 99, 132, 0.5)',
                        'rgba(54, 162, 235, 0.5)',
                        'rgba(255, 206, 86, 0.5)',
                        'rgba(75, 192, 192, 0.5)'
                    ]
                }]
            };
    
            const ctx = document.getElementById('myChart').getContext('2d');
            new Chart(ctx, {
                type: 'bar',
                data: {
                    labels: ['Plastic', 'Paper', 'Glass', 'Organic'],
                    datasets: [{
                        label: 'Total Waste Collected (kg)',
                        data: [45, 30, 15, 25],
                        backgroundColor: [
                            'rgba(255, 99, 132, 0.5)',
                            'rgba(54, 162, 235, 0.5)',
                            'rgba(255, 206, 86, 0.5)',
                            'rgba(75, 192, 192, 0.5)'
                        ],
                        borderColor: [
                            'rgba(255, 99, 132, 1)',
                            'rgba(54, 162, 235, 1)',
                            'rgba(255, 206, 86, 1)',
                            'rgba(75, 192, 192, 1)'
                        ],
                        borderWidth: 1
                    }]
                },
                options: {
                    responsive: true,
                    maintainAspectRatio: false,
                    scales: {
                        y: {
                            beginAtZero: true,
                            title: {
                                display: true,
                                text: 'Total Weight (kg)'
                            }
                        }
                    },
                    plugins: {
                        title: {
                            display: true,
                            text: 'Total Waste Reported by You'
                        }
                    }
                }
            });

            // Initialize the calendar after the page loads
                new Calendar();
        });

    </script>
    

</body>

</html>
