
<?php
// EE 470 - Database Assignment Part 2
// Miguel Valentin
// Decode Base64 sensor data and insert into MySQL

header("Content-Type: text/html; charset=utf-8");
header("Cache-Control: no-store, no-cache, must-revalidate");

// Read the encoded message from the URL
$query = $_SERVER['QUERY_STRING'] ?? '';

// Decode the Base64 message
$decoded = base64_decode($query, true);

// Check whether decoding worked
if ($decoded === false) {
    http_response_code(400);
    exit("ERROR: Invalid Base64 message.");
}

// Convert decoded parameters into an array
parse_str($decoded, $params);

// Extract sensor information
$node = $params['node_name'] ?? $params['nodeId'] ?? '';
$temp = $params['temperature'] ?? $params['nodeTemp'] ?? null;
$humidity = $params['humidity'] ?? null;
$time = $params['time_received'] ?? $params['timeReceived'] ?? null;

// Display decoded values for demonstration
echo "<h2>Decoded Sensor Data</h2>";

echo "<p>Node: " . htmlspecialchars((string)$node) . "</p>";
echo "<p>Temperature: " . htmlspecialchars((string)$temp) . "</p>";
echo "<p>Humidity: " . htmlspecialchars((string)$humidity) . "</p>";
echo "<p>Time: " . htmlspecialchars((string)$time) . "</p>";

// Validate required values
if (!is_string($node) ||
    !preg_match('/^node_[0-9]{1,5}$/', $node) ||
    !is_scalar($temp) ||
    !is_scalar($humidity) ||
    !is_numeric($temp) ||
    !is_numeric($humidity) ||
    !is_string($time) ||
    $time === '') {

    http_response_code(400);
    exit("ERROR: Missing or invalid sensor data.");
}

$temp = (float)$temp;
$humidity = (float)$humidity;

// Validate allowed ranges
if (!is_finite($temp) || !is_finite($humidity) ||
    $temp < -10 || $temp > 100 ||
    $humidity < 0 || $humidity > 100) {

    http_response_code(400);
    exit("ERROR: Sensor values are outside allowed ranges.");
}

// Validate timestamp
$dt = DateTimeImmutable::createFromFormat(
    '!Y-m-d H:i:s', $time
);

if (!$dt || $dt->format('Y-m-d H:i:s') !== $time) {
    http_response_code(400);
    exit("ERROR: Invalid timestamp.");
}

// Connect to MySQL
require_once __DIR__ . '/db_connect.php';

// Insert the decoded sensor reading
try {
    $stmt = $conn->prepare(
        "INSERT INTO sensor_data
         (node_name, time_received, temperature, humidity)
         VALUES (?, ?, ?, ?)"
    );

    $stmt->bind_param("ssdd", $node, $time, $temp, $humidity);
    $stmt->execute();

    echo "<h2>SUCCESS: Sensor reading inserted.</h2>";

    $stmt->close();

} catch (mysqli_sql_exception $e) {

    if ($e->getCode() == 1452) {
        http_response_code(400);
        echo "<h2>ERROR: Sensor is not registered.</h2>";
    } elseif ($e->getCode() == 1062) {
        http_response_code(409);
        echo "<h2>ERROR: Duplicate node and timestamp.</h2>";
    } else {
        error_log($e->getMessage());
        http_response_code(500);
        echo "<h2>ERROR: Database insertion failed.</h2>";
    }
}

$conn->close();
?>
