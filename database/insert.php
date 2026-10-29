
<?php
// EE 470 - Database Assignment Part 2
// Miguel Valentin
// Insert sensor readings using HTTP GET

// Prevent cached responses
header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
header("Pragma: no-cache");
header("Content-Type: text/html; charset=utf-8");

// Style the response messages
echo '<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Sensor Database Response</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6fa;
            color: #243b64;
            margin: 40px;
        }

        .response {
            font-family: Arial, sans-serif;
            font-size: 32px;
            font-weight: bold;
            line-height: 1.5;
            padding: 25px;
            background-color: white;
            border: 2px solid #243b64;
            border-radius: 10px;
        }
    </style>
</head>
<body>
<div class="response">';

// Connect to the database
require_once __DIR__ . '/db_connect.php';

// Read parameters from the URL
$node = $_GET['node_name'] ?? '';
$temp = $_GET['temperature'] ?? null;
$humidity = $_GET['humidity'] ?? null;
$time = $_GET['time_received'] ?? null;

// Validate the sensor name
if (!is_string($node) ||
    !preg_match('/^node_[0-9]{1,5}$/', $node) ||
    strlen($node) > 10) {

    http_response_code(400);
    exit("ERROR: Invalid node name.");
}

// Validate temperature and humidity
if ($temp === null || $humidity === null ||
    !is_scalar($temp) || !is_scalar($humidity) ||
    !is_numeric($temp) || !is_numeric($humidity)) {

    http_response_code(400);
    exit("ERROR: Temperature and humidity are required.");
}

$temp = (float)$temp;
$humidity = (float)$humidity;

// Check accepted sensor ranges
if (!is_finite($temp) || !is_finite($humidity) ||
    $temp < -10 || $temp > 100 ||
    $humidity < 0 || $humidity > 100) {

    http_response_code(400);
    exit("ERROR: Sensor values are outside allowed ranges.");
}

// Validate timestamp if provided
if ($time !== null && $time !== '') {

    if (!is_string($time)) {
        http_response_code(400);
        exit("ERROR: Invalid timestamp.");
    }

    $dt = DateTimeImmutable::createFromFormat(
        '!Y-m-d H:i:s', $time
    );

    if (!$dt ||
        $dt->format('Y-m-d H:i:s') !== $time) {

        http_response_code(400);
        exit("ERROR: Invalid timestamp. Use YYYY-MM-DD HH:MM:SS.");
    }
}

// Execute the INSERT command
try {

    // If no timestamp is supplied, use database default
    if ($time === null || $time === '') {

        $sql = "INSERT INTO sensor_data
                (node_name, temperature, humidity)
                VALUES (?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("sdd", $node, $temp, $humidity);

    } else {

        $sql = "INSERT INTO sensor_data
                (node_name, time_received, temperature, humidity)
                VALUES (?, ?, ?, ?)";

        $stmt = $conn->prepare($sql);
        $stmt->bind_param("ssdd", $node, $time, $temp, $humidity);
    }

    $stmt->execute();

    echo "SUCCESS: Sensor reading inserted.";

} catch (mysqli_sql_exception $e) {

    $code = $e->getCode();

    if ($code == 1452) {

        http_response_code(400);
        echo "ERROR: Sensor is not registered.";

    } elseif ($code == 1062) {

        http_response_code(409);
        echo "ERROR: Duplicate node and timestamp.";

    } elseif ($code == 4025 || $code == 3819) {

        http_response_code(400);
        echo "ERROR: Sensor value violates a constraint.";

    } else {

        error_log("Sensor insert failed: " . $e->getMessage());
        http_response_code(500);
        echo "ERROR: Database insertion failed.";
    }
}

// Close the statement and database connection
if (isset($stmt)) {
    $stmt->close();
}

$conn->close();

// Close the HTML response
echo '</div></body></html>';
?>
