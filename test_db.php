<?php
// Test DNS resolution
$host = 'sql207.infinityfree.com';
$ip = gethostbyname($host);
echo "Hostname: $host\n";
echo "IP Address: $ip\n";

// Try to get IP using nslookup
echo "\nTrying nslookup:\n";
$nslookup = shell_exec("nslookup $host");
echo $nslookup;

// Test database connection with hostname
echo "\nTrying connection with hostname:\n";
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

// Test database connection with direct IP (if available)
echo "\n\nTrying connection with direct IP:\n";
try {
    $conn = new mysqli('185.27.134.11', 'if0_39005718', 'BinIt020804', 'if0_39005718_binit_db');
    if ($conn->connect_error) {
        echo "Connection failed: " . $conn->connect_error;
    } else {
        echo "Database connection successful!";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage();
}
?> 