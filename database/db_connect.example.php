
<?php
// EE 470 - Database Assignment Part 2
// Miguel Valentin
// Example MySQL database connection
// Replace placeholders with your own credentials

$host = "localhost";
$dbname = "YOUR_DATABASE_NAME";
$username = "YOUR_DATABASE_USERNAME";
$password = "YOUR_DATABASE_PASSWORD";

// Connect to MySQL
$conn = new mysqli(
    $host,
    $username,
    $password,
    $dbname
);

// Check connection
if ($conn->connect_error) {
    error_log("Database connection failed.");
    http_response_code(500);
    exit("Database connection failed.");
}

// Set character encoding
$conn->set_charset("utf8mb4");

// Set database session time zone
// UTC-7 is Pacific Daylight Time only.
// Change to UTC-8 during Pacific Standard Time.
$conn->query("SET time_zone = '-07:00'");
?>
