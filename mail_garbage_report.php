<?php
// session_destroy();
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require 'PHPMailer/src/PHPMailer.php';
require 'PHPMailer/src/SMTP.php';
require 'PHPMailer/src/Exception.php';

// session_destroy();
error_reporting(E_ALL);
ini_set('display_errors', 1);

date_default_timezone_set('Asia/Kolkata');

$config = [
    'host' => 'sql207.infinityfree.com',
    'user' => 'if0_39005718',
    'pass' => 'BinIt020804',
    'db' => 'if0_39005718_binit_db'
];

try {
    $conn = new mysqli($config['host'], $config['user'], $config['pass'], $config['db']);
    if ($conn->connect_error) {
        throw new Exception("Database connection failed: " . $conn->connect_error);
    }
    $conn->set_charset("utf8mb4");
} catch (Exception $e) {
    die("❌ Connection error: " . $e->getMessage());
}

ensureTables($conn);
$lastReportDate = null;
$daysPassed = 'N/A';
$priority = 'None';
$priorityColor = '#6c757d'; // Gray

$result = $conn->query("SELECT sent_at FROM report_sent_log_tb ORDER BY sent_at DESC LIMIT 1");

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    $lastReportDate = new DateTime($row['sent_at']);
    $today = new DateTime();
    $daysPassed = $today->diff($lastReportDate)->days;
    
    // Changed priority threshold from 3 to 2 days
    $priority = ($daysPassed > 3) ? 'High' : (($daysPassed > 2) ? 'Medium' : 'Low');
    
    // Set priority colors
    $priorityColors = [
        'High' => '#dc3545',    // Red
        'Medium' => '#ffc107',  // Yellow
        'Low' => '#28a745'      // Green
    ];
    $priorityColor = $priorityColors[$priority];
}

$msg = "";

$recentReports = getRecentReports($conn);

if (isset($_POST['send_now'])) {
    try {
        // Verify that recipients are selected
        if(!isset($_POST['recipients']) || empty($_POST['recipients'])) {
            throw new Exception("Please select at least one recipient");
        }
        
        $recipients = $_POST['recipients'];
        
        // Get report data from last report date or last 7 days if no previous report
        $fromDate = ($lastReportDate) ? $lastReportDate->format('Y-m-d H:i:s') : date('Y-m-d H:i:s', strtotime('-7 days'));
        $reportData = getReportData($conn, $fromDate);
        
        // Only send if we have data or force send is checked
        if ($reportData['rowCount'] > 0 || isset($_POST['force_send'])) {
            $success = sendReportEmail($reportData, $recipients);
            
            if ($success) {
                // Update the last sent time
                $now = date("Y-m-d H:i:s");
                
                $recipientEmails = implode(", ", $recipients);
                $recipientCount = count($recipients);

                $stmt = $conn->prepare("INSERT INTO report_sent_log_tb (sent_at, recipient_count, recipeint_to) VALUES (?, ?, ?)");
                $stmt->bind_param("sis", $now, $recipientCount, $recipientEmails);
                $stmt->execute();
                $stmt->close();
                header("Location: ".$_SERVER['PHP_SELF']."?success=1");
                exit;
            }
        } else {
            $msg = "<div class='alert alert-warning'>No garbage reports found since the last report was sent. <strong>Use 'Force Send' to send anyway.</strong></div>";
        }
    } catch (Exception $e) {
        $msg = "<div class='alert alert-danger'>❌ Error sending report: " . $e->getMessage() . "</div>";
    }
}

if (isset($_GET['success'])) {
    $msg = "<div class='alert alert-success'>✅ Report mail sent successfully.</div>";
}

$dailyData = getDailyUploadsData($conn);
$classData = getGarbageClassData($conn);
$topUsers = getTopContributorsData($conn);
$hotspotData = getHotspotData($conn);

// FUNCTIONS
function ensureTables($conn) {

    $conn->query("
        CREATE TABLE IF NOT EXISTS authorities_tb (
            id INT AUTO_INCREMENT PRIMARY KEY,
            name VARCHAR(100),
            email VARCHAR(255) UNIQUE
        )
    ");

    $conn->query("INSERT IGNORE INTO authorities_tb (name, email) VALUES 
        ('Srishti Biswas', 'srishtibiswas284@gmail.com'),
        ('Ronit Rathore', 'ronitrathoree1804@gmail.com'),
        ('Sahina Firdosh', 'sahinafirdosh.sf@gmail.com')
    ");

    $conn->query("
        CREATE TABLE IF NOT EXISTS report_sent_log_tb (
            id INT AUTO_INCREMENT PRIMARY KEY,
            sent_at DATETIME NOT NULL,
            recipient_count INT DEFAULT 1,
            recipeint_to VARCHAR(255)
        )
    ");
}

function getRecentReports($conn) {
    $reports = [];
    $result = $conn->query("SELECT sent_at, recipeint_to, recipient_count FROM report_sent_log_tb ORDER BY sent_at DESC LIMIT 5");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $reports[] = $row;
        }
    }
    return $reports;
}

function getReportData($conn, $lastReportDate) {
    // Changed to use last report date instead of fixed 24 hours
    $query = "
        SELECT u.username, u.latitude, u.longitude, u.area, u.city, u.image_path, 
               g.garbage_classification, u.timestamp
        FROM user_input_tb u
        JOIN garbage_classification_tb g ON u.username = g.username AND u.image_path = g.image_path
        WHERE u.timestamp > ?
        ORDER BY u.timestamp DESC
    ";
    
    $stmt = $conn->prepare($query);
    $stmt->bind_param("s", $lastReportDate);
    $stmt->execute();
    $result = $stmt->get_result();
    $rowCount = $result->num_rows;
    
    return [
        'result' => $result,
        'rowCount' => $rowCount,
        'fromDate' => $lastReportDate
    ];
}

function sendReportEmail($reportData, $recipients) {
    $result = $reportData['result'];
    $rowCount = $reportData['rowCount'];
    $fromDate = $reportData['fromDate'];
    
    // Format the date nicely for display
    $fromDateFormatted = date('d M Y, h:i A', strtotime($fromDate));
    
    if ($rowCount > 0) {
        $table = "<h3>Garbage Reports since $fromDateFormatted</h3>";
        $table .= "<p><strong>Total Reports:</strong> $rowCount</p>";
        $table .= "<table border='1' cellpadding='8' cellspacing='0' style='border-collapse: collapse; width: 100%;'>
                    <tr style='background-color: #f2f2f2;'>
                        <th>Username</th>
                        <th>Classification</th>
                        <th>Location</th>
                        <th>Image</th>
                        <th>Date/Time</th>
                    </tr>";
        
        while ($row = $result->fetch_assoc()) {
            $location = htmlspecialchars("{$row['area']}, {$row['city']} ({$row['latitude']}, {$row['longitude']})");
            $imageLink = "http://your-domain.com/Major_Project/uploads/" . htmlspecialchars($row['image_path']);
            $classification = htmlspecialchars($row['garbage_classification']);
            
            $classColors = [
                'Plastic' => '#28a745',
                'Paper' => '#ffc107',
                'Metal' => '#17a2b8', 
                'Glass' => '#6610f2',
                'Organic' => '#fd7e14',
                'Other' => '#dc3545'
            ];
            
            $classColor = isset($classColors[$classification]) ? $classColors[$classification] : '#6c757d';
            
            $table .= "<tr>
                        <td>" . htmlspecialchars($row['username']) . "</td>
                        <td style='color: $classColor; font-weight: bold;'>{$classification}</td>
                        <td>{$location}</td>
                        <td><a href='{$imageLink}' target='_blank' style='color: #007BFF; text-decoration: none;'>View Image</a></td>
                        <td>" . date('d M Y, h:i A', strtotime($row['timestamp'])) . "</td>
                      </tr>";
        }
        $table .= "</table>";
    } else {
        $table = "<p>No garbage reports submitted since $fromDateFormatted.</p>";
    }

    $mail = new PHPMailer(true);
    try {
        // SMTP settings
        $mail->isSMTP();
        $mail->Host       = 'smtp.gmail.com';
        $mail->SMTPAuth   = true;
        $mail->Username   = 'binit.trashduty@gmail.com';
        $mail->Password   = 'xxpq hmon wjpa rmpl'; // Use App Password
        $mail->SMTPSecure = 'tls';
        $mail->Port       = 587;

        // Recipients
        $mail->setFrom('binit.trashduty@gmail.com', 'BinIt Admin');
        foreach ($recipients as $email) {
            $mail->addAddress($email);
        }
        
        $mail->addReplyTo('binit.trashduty@gmail.com', 'BinIt Support');
        
        $mail->isHTML(true);
        $mail->Subject = '🚨 BinIt Garbage Report - ' . date('d M Y');
        
        // Load email template
        $emailTemplate = '
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; }
                .container { max-width: 800px; margin: 0 auto; padding: 20px; }
                .header { background-color: #40b3a2; color: white; padding: 15px; text-align: center; }
                .content { padding: 20px; background-color: #f9f9f9; }
                .footer { text-align: center; margin-top: 20px; font-size: 12px; color: #777; }
                table { width: 100%; border-collapse: collapse; margin: 20px 0; }
                th { background-color: #f2f2f2; }
                th, td { padding: 10px; border: 1px solid #ddd; }
            </style>
        </head>
        <body>
            <div class="container">
                <div class="header">
                    <h2>BinIt - Garbage Report Summary</h2>
                    <p>' . date('d M Y') . '</p>
                </div>
                <div class="content">
                    <p>Dear Authorities,</p>
                    <p>This is an automated report of garbage submissions detected through our BinIt application since ' . $fromDateFormatted . '. Please review and take necessary action to ensure these areas are properly cleaned.</p>
                    
                    ' . $table . '
                    
                    <p>The BinIt application continues to help citizens report garbage in their neighborhoods. Your prompt action will improve community cleanliness and health.</p>
                    
                    <p>Thank you for your attention to this matter.</p>
                    
                    <p>Best regards,<br>BinIt Administration Team</p>
                </div>
                <div class="footer">
                    <p>This is an automated message from BinIt. Please do not reply directly to this email.</p>
                    <p>&copy; ' . date('Y') . ' BinIt. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>
        ';
        
        $mail->Body = $emailTemplate;
        $mail->AltBody = strip_tags(str_replace('<br>', "\n", $table));
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        throw new Exception($mail->ErrorInfo);
    }
}

function getDailyUploadsData($conn) {
    $dailyData = ['labels' => [], 'counts' => []];
    for ($i = 6; $i >= 0; $i--) {
        $day = date('Y-m-d', strtotime("-$i days"));
        $dayLabel = date('D, M d', strtotime("-$i days")); // Format: Mon, Jan 01
        $res = $conn->query("SELECT COUNT(*) AS count FROM user_input_tb WHERE DATE(timestamp) = '$day'");
        $count = $res->fetch_assoc()['count'];
        $dailyData['labels'][] = $dayLabel;
        $dailyData['counts'][] = $count;
    }
    return $dailyData;
}

function getGarbageClassData($conn) {
    $classData = ['labels' => [], 'counts' => [], 'colors' => []];
    $colors = [
        'Plastic' => '#28a745',
        'Paper' => '#ffc107',
        'Metal' => '#17a2b8', 
        'Glass' => '#6610f2',
        'Organic' => '#fd7e14',
        'Other' => '#dc3545'
    ];
    
    $classRes = $conn->query("SELECT garbage_classification, COUNT(*) AS count FROM garbage_classification_tb GROUP BY garbage_classification");
    if ($classRes) {
        while ($row = $classRes->fetch_assoc()) {
            $class = $row['garbage_classification'];
            $classData['labels'][] = $class;
            $classData['counts'][] = $row['count'];
            $classData['colors'][] = isset($colors[$class]) ? $colors[$class] : '#6c757d';
        }
    }
    return $classData;
}

function getTopContributorsData($conn) {
    $topUsers = ['labels' => [], 'counts' => []];
    $userRes = $conn->query("SELECT username, COUNT(*) AS uploads FROM user_input_tb GROUP BY username ORDER BY uploads DESC LIMIT 5");
    if ($userRes) {
        while ($row = $userRes->fetch_assoc()) {
            $topUsers['labels'][] = $row['username'];
            $topUsers['counts'][] = $row['uploads'];
        }
    }
    return $topUsers;
}

function getHotspotData($conn) {
    $hotspotData = ['areas' => [], 'counts' => []];
    $hotspotQuery = "
        SELECT area, city, COUNT(*) as reports
        FROM user_input_tb
        WHERE timestamp >= NOW() - INTERVAL 30 DAY
        GROUP BY area, city
        ORDER BY reports DESC
        LIMIT 5
    ";
    
    $hotspotRes = $conn->query($hotspotQuery);
    if ($hotspotRes) {
        while ($row = $hotspotRes->fetch_assoc()) {
            $hotspotData['areas'][] = "{$row['area']}, {$row['city']}";
            $hotspotData['counts'][] = $row['reports'];
        }
    }
    return $hotspotData;
}

// Get all available authorities for dropdown
function getAuthorities($conn) {
    $authorities = [];
    $result = $conn->query("SELECT id, name, email FROM authorities_tb ORDER BY name");
    if ($result && $result->num_rows > 0) {
        while ($row = $result->fetch_assoc()) {
            $authorities[] = $row;
        }
    }
    return $authorities;
}

// Get available authorities for the dropdown
$authorities = getAuthorities($conn);
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>BinIt Admin - Garbage Report Dashboard</title>
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
    <link href="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/css/select2.min.css" rel="stylesheet" />
    <script src="https://code.jquery.com/jquery-3.6.0.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/select2@4.1.0-rc.0/dist/js/select2.min.js"></script>
    <link rel="stylesheet" href="mail_report.css">
    <link rel="icon" href="logo.png" type="image/x-icon">
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
            <img src="/Major_Project/static/logo.png" alt="BinIt Logo" class="logo">
            <p>BinIt</p>
        </div>
        <div class="nav_right">
            <img src="/Major_Project/static/user.png" alt="User-Profile" class="Profile_pic" id="profilePic">
            <!-- <div class="username" id="username">Admin</div> -->
            <div class="username" id="usernameDisplay">Admin</div>
            <div id="profileModal" class="profile-modal">
        <div class="modal-content">
            <ul class="profile-options">
                <li>
                    <a href="#">
                        <img src="Main/user_profile.png" alt="My Profile">
                        <span>My Profile</span>
                    </a>
                </li>
                <li>
                    <a href="#">
                        <img src="Main/change_pass.png" alt="Change Password">
                        <span>Change Password</span>
                    </a>
                </li>
                <li>
                    <a href="Main/logout.php">
                        <img src="Main/logout.png" alt="Log Out">
                        <span>Log Out</span>
                    </a>
                </li>
            </ul>
        </div>
    </div>
        </div>
    </nav>
    
    <div class="dashboard-container">
        <div class="dashboard">
            <div class="dashboard-header">
                <h2>Garbage Report Dashboard</h2>
                <div class="date-display">
                    <span><?= date('l, d M Y') ?></span>
                </div>
            </div>
            
            <?php if ($msg): ?>
                <?= $msg ?>
            <?php endif; ?>
            
            <div class="report-stats">
                <div class="stats-card">
                    <div class="stats-header">Report Status</div>
                    <div class="status">
                        <p><strong>Last Report Sent:</strong> 
                        <?= $lastReportDate ? $lastReportDate->format('d M Y, h:i A') : 'No reports sent yet' ?>
                        </p>
                        <p><strong>Days Since Last Report:</strong> <span class="badge"><?= $daysPassed ?> <?= is_numeric($daysPassed) ? 'day(s)' : '' ?></span></p>
                        <p><strong>Priority:</strong> <span class="priority-badge" style="background-color: <?= $priorityColor ?>"><?= $priority ?></span></p>
                    </div>
                    <form method="post" class="report-actions">
                        <div class="inline-form-group">
                            <label for="recipients"><strong>Select Authorities to Send Report:</strong></label>
                            <select name="recipients[]" multiple class="recipient-select" required>
                                <?php foreach ($authorities as $authority): ?>
                                    <option value="<?= $authority['email'] ?>"><?= $authority['name'] ?> (<?= $authority['email'] ?>)</option>
                                <?php endforeach; ?>
                            </select>
                        </div>

                        <div class="select-info">Hold Ctrl (or Cmd) to select multiple recipients</div>

                        <button type="submit" name="send_now" class="btn primary">📩 Send Report Now</button>
                        <label class="force-send">
                            <input type="checkbox" name="force_send"> Force send even if no new reports
                        </label>
                    </form>
                </div>
                
                <div class="stats-card">
                    <div class="stats-header">Recent Reports</div>
                    <div class="recent-reports">
                        <?php if (count($recentReports) > 0): ?>
                            <table class="mini-table">
                                <tr>
                                    <th>Date</th>
                                    <th>Recipients</th>
                                </tr>
                                <?php foreach ($recentReports as $report): ?>
                                <tr>
                                    <td><?= date('d M Y, h:i A', strtotime($report['sent_at'])) ?></td>
                                    <td><?php
                                        if (!empty($report['recipeint_to'])) {
                                            $emails = explode(',', $report['recipeint_to']);
                                            foreach ($emails as $email) {
                                                echo htmlspecialchars(trim($email)) . "<br>";
                                            }
                                        } else {
                                            echo "—"; // or "No recipients"
                                        }
                                        ?></td>
                                </tr>
                                <?php endforeach; ?>
                            </table>
                        <?php else: ?>
                            <p>No recent reports found.</p>
                        <?php endif; ?>
                    </div>
                </div>
            </div>

            <div class="chart-container">
                <div class="chart-card">
                    <h3>Image Upload Trends (Last 7 Days)</h3>
                    <canvas id="dailyUploads"></canvas>
                </div>

                <div class="chart-card">
                    <h3>Garbage Classification Distribution</h3>
                    <canvas id="garbageTypes"></canvas>
                </div>
            </div>
            
            <div class="chart-container">
                <div class="chart-card">
                    <h3>Top Contributors</h3>
                    <canvas id="topUsers"></canvas>
                </div>
                
                <div class="chart-card">
                    <h3>Garbage Hotspots (30 Days)</h3>
                    <canvas id="hotspots"></canvas>
                </div>
            </div>
        </div>
    </div>

<script>
    // form select option
    $(document).ready(function () {
        $('.recipient-select').select2({
            placeholder: "Select authorities...",
            width: '250px'
        });
    });

    // Improved charts with better styling
    Chart.defaults.font.family = '-apple-system, BlinkMacSystemFont, "Segoe UI", Roboto, sans-serif';
    Chart.defaults.color = '#555';
    
    // Daily uploads chart
    const dailyCtx = document.getElementById('dailyUploads').getContext('2d');
    const dailyUploads = new Chart(dailyCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($dailyData['labels']) ?>,
            datasets: [{
                label: 'Uploads',
                data: <?= json_encode($dailyData['counts']) ?>,
                backgroundColor: '#40b3a2',
                borderColor: '#2a9080',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.7)',
                    padding: 10,
                    titleFont: {
                        size: 14
                    },
                    bodyFont: {
                        size: 14
                    }
                }
            },
            scales: {
                y: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Number of Uploads',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                x: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Date',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });

    // Garbage types chart
    const garbageCtx = document.getElementById('garbageTypes').getContext('2d');
    const garbageChart = new Chart(garbageCtx, {
        type: 'doughnut',
        data: {
            labels: <?= json_encode($classData['labels']) ?>,
            datasets: [{
                data: <?= json_encode($classData['counts']) ?>,
                backgroundColor: <?= json_encode($classData['colors']) ?>,
                borderColor: '#ffffff',
                borderWidth: 2
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    position: 'right',
                    labels: {
                        padding: 20,
                        usePointStyle: true,
                        pointStyle: 'circle'
                    }
                },
                tooltip: {
                    backgroundColor: 'rgba(0,0,0,0.7)',
                    padding: 10,
                    callbacks: {
                        label: function(context) {
                            let label = context.label || '';
                            let value = context.raw || 0;
                            let total = context.dataset.data.reduce((a, b) => a + b, 0);
                            let percentage = Math.round((value / total) * 100);
                            return `${label}: ${value} (${percentage}%)`;
                        }
                    }
                }
            },
            cutout: '60%'
        }
    });

    // Top users chart
    const userCtx = document.getElementById('topUsers').getContext('2d');
    const topUsers = new Chart(userCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($topUsers['labels']) ?>,
            datasets: [{
                label: 'Uploads',
                data: <?= json_encode($topUsers['counts']) ?>,
                backgroundColor: '#6f42c1',
                borderColor: '#5a32a3',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Number of Uploads',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Username',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
    });
    
    // Hotspots chart
    const hotspotCtx = document.getElementById('hotspots').getContext('2d');
    const hotspotsChart = new Chart(hotspotCtx, {
        type: 'bar',
        data: {
            labels: <?= json_encode($hotspotData['areas']) ?>,
            datasets: [{
                label: 'Reports',
                data: <?= json_encode($hotspotData['counts']) ?>,
                backgroundColor: '#dc3545',
                borderColor: '#c82333',
                borderWidth: 1,
                borderRadius: 4
            }]
        },
        options: {
            indexAxis: 'y',
            responsive: true,
            maintainAspectRatio: false,
            plugins: {
                legend: {
                    display: false
                }
            },
            scales: {
                x: {
                    beginAtZero: true,
                    ticks: {
                        precision: 0
                    },
                    grid: {
                        color: 'rgba(0,0,0,0.05)'
                    },
                    title: {
                        display: true,
                        text: 'Number of Reports',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                },
                y: {
                    grid: {
                        display: false
                    },
                    title: {
                        display: true,
                        text: 'Area',
                        font: {
                            size: 12,
                            weight: 'bold'
                        }
                    }
                }
            }
        }
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