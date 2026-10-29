
<?php
// EE 470 - Database Assignment Part 2
// Miguel Valentin
// Display sensor readings in JSON format

// Prevent cached data
header("Cache-Control: no-store, no-cache, must-revalidate");
header("Content-Type: text/html; charset=utf-8");

// Connect to MySQL
require_once __DIR__ . '/db_connect.php';

// Select readings from node_1
$sql = "SELECT node_name, time_received,
               temperature, humidity
        FROM sensor_data
        WHERE node_name = 'node_1'
        ORDER BY time_received";

// Execute the query
$result = $conn->query($sql);

// Store sensor readings in an array
$sensor_data = [];

while ($row = $result->fetch_assoc()) {

    $sensor_data[] = [
        "node_name" => $row["node_name"],
        "time_received" => $row["time_received"],
        "temperature" => (float)$row["temperature"],
        "humidity" => (float)$row["humidity"]
    ];
}

// Convert PHP array into formatted JSON
$json_output = json_encode(
    $sensor_data,
    JSON_PRETTY_PRINT
);

// Close database connection
$conn->close();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport"
          content="width=device-width, initial-scale=1.0">

    <title>Sensor Node 1 - JSON Data</title>

    <style>
        body {
            font-family: Arial, sans-serif;
            background-color: #f4f6fa;
            color: #243b64;
            margin: 30px;
        }

        h1 {
            font-size: 28px;
        }

        pre {
            font-family: Consolas, monospace;
            font-size: 22px;
            line-height: 1.6;
            background-color: white;
            padding: 25px;
            border: 2px solid #243b64;
            border-radius: 10px;
            overflow-x: auto;
        }
    </style>
</head>

<body>

<h1>Sensor Node 1 - JSON Data</h1>

<pre><?= htmlspecialchars(
    $json_output,
    ENT_QUOTES,
    'UTF-8'
) ?></pre>

</body>
</html>
