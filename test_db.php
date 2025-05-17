<?php
// Test DNS resolution
$host = 'sql207.infinityfree.com';
$ip = gethostbyname($host);
echo "Hostname: $host\n";
echo "IP Address: $ip\n";

// Test database connection
try {
    $conn = new mysqli('sql207.infinityfree.com', 'if0_39005718', 'BinIt020804', 'if0_39005718_binit_db');
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    } else {
        echo "Database connection successful!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 