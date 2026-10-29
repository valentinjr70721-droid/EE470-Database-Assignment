
<?php
// EE 470 - Database Assignment Part 2
// Miguel Valentin
// Display sensor registration and measurement data

// Connect to MySQL
require_once __DIR__ . '/db_connect.php';

// Get the selected sensor from the URL.
// Default to node_1 if no sensor is selected.
$selected_node = $_GET['node'] ?? 'node_1';

// Retrieve the registered sensor names
$nodes_result = $conn->query(
    "SELECT node_name
     FROM sensor_register
     ORDER BY node_name"
);

// Store the registered node names
$nodes = [];

while ($row = $nodes_result->fetch_assoc()) {
    $nodes[] = $row['node_name'];
}

// Only allow registered sensors to be selected
if (!in_array($selected_node, $nodes, true)) {
    $selected_node = $nodes[0] ?? '';
}

 // Get temperature readings for node_1
 // Sort by time so the graph displays chronologically
 
// Get graph data for the selected sensor
$chart_stmt = $conn->prepare(
    "SELECT time_received, temperature
     FROM sensor_data
     WHERE node_name = ?
     ORDER BY time_received"
);

$chart_stmt->bind_param("s", $selected_node);
$chart_stmt->execute();
$chart_result = $chart_stmt->get_result();


 if (!$chart_result) {
     error_log("Failed to retrieve graph data.");
     http_response_code(500);
     exit("Unable to load graph data.");
 }

 // Store timestamps and temperatures in arrays
 $chart_times = [];
 $chart_temperatures = [];

 while ($row = $chart_result->fetch_assoc()) {
     
    $chart_times[] = date(
        'm/d H:i',
        strtotime($row['time_received'])
    );

     $chart_temperatures[] = (float)$row['temperature'];
 }


// Get all registered sensors
$register_result = $conn->query(
    "SELECT * FROM sensor_register ORDER BY node_name"
);

// Get sensor measurements sorted by node and time
$data_result = $conn->query(
    "SELECT * FROM sensor_data
     ORDER BY node_name, time_received"
);


// Calculate all four statistics for the selected sensor
$average_stmt = $conn->prepare(
    "SELECT
        AVG(temperature) AS avg_temperature,
        AVG(humidity) AS avg_humidity,
        MAX(temperature) AS max_temperature,
        MIN(humidity) AS min_humidity
     FROM sensor_data
     WHERE node_name = ?"
);

$average_stmt->bind_param("s", $selected_node);
$average_stmt->execute();
$average_result = $average_stmt->get_result();



// Check whether the average query worked
if (!$average_result) {
    error_log("Failed to calculate sensor averages.");
    http_response_code(500);
    exit("Unable to calculate averages.");
}

// Retrieve the calculated averages
$averages = $average_result->fetch_assoc();

// Stop if either query fails
if (!$register_result || !$data_result) {
    error_log("Failed to retrieve sensor data.");
    http_response_code(500);
    exit("Unable to load sensor information.");
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title>EE 470 Sensor Database</title>
    <style>
        body {
            font-family: Arial, sans-serif;
            margin: 30px;
            background-color: #f4f6fa;
        }

        h1, h2 {
            color: #243b64;
        }

        table {
            border-collapse: collapse;
            width: 100%;
            margin-bottom: 30px;
            background-color: white;
        }

        th, td {
            border: 1px solid #ccc;
            padding: 10px;
            text-align: center;
        }

        th {
            background-color: #243b64;
            color: white;
        }

        tr:nth-child(even) {
            background-color: #edf2f8;
        }
        
        /* Make the webpage easier to read */
        
        body {
            font-family: Arial, sans-serif;
            font-size: 18px;
            margin: 30px;
        }
        
        /* Main page heading */
        h1 {
            font-size: 32px;
        }
        
        /* Section headings */
        h2 {
            font-size: 26px;
            margin-top: 30px;
        }
        
        /* Sensor selection dropdown */
        select {
            font-size: 20px;
            padding: 10px 15px;
            min-width: 180px;
            border: 2px solid #243b64;
            border-radius: 6px;
            background-color: white;
            color: #243b64;
            cursor: pointer;
        }
        
        /* Dropdown label */
        label {
            font-size: 20px;
            font-weight: bold;
            margin-right: 10px;
        }
        
        /* Statistics */
        p {
            font-size: 19px;
            line-height: 1.8;
        }
        
        /* Table text */
        table {
            font-size: 16px;
        }
        
        th, td {
            padding: 12px;
        }
    
        /* Make the sensor dropdown text larger */
        select {
            font-size: 22px !important;
            padding: 12px 18px;
            min-width: 200px;
        }
        
        /* Make the dropdown options larger */
        select option {
            font-size: 22px !important;
            padding: 10px;
        }

    </style>
</head>

<body>

<h1>EE 470 - Sensor Database</h1>
<p>Student: Miguel Valentin</p>

<h2>Registered Sensors</h2>

<table>
    <tr>
        <th>Node Name</th>
        <th>Manufacturer</th>
        <th>Longitude</th>
        <th>Latitude</th>
    </tr>

    <?php while ($row = $register_result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['node_name']) ?></td>
        <td><?= htmlspecialchars($row['manufacturer']) ?></td>
        <td><?= htmlspecialchars($row['longitude']) ?></td>
        <td><?= htmlspecialchars($row['latitude']) ?></td>
    </tr>
    <?php endwhile; ?>

</table>

<h2>Sensor Measurements</h2>

<table>
    <tr>
        <th>Node Name</th>
        <th>Time Received</th>
        <th>Temperature (C)</th>
        <th>Humidity (%)</th>
    </tr>

    <?php while ($row = $data_result->fetch_assoc()): ?>
    <tr>
        <td><?= htmlspecialchars($row['node_name']) ?></td>
        <td><?= htmlspecialchars($row['time_received']) ?></td>
        <td><?= htmlspecialchars($row['temperature']) ?></td>
        <td><?= htmlspecialchars($row['humidity']) ?></td>
    </tr>
    <?php endwhile; ?>

</table>

<!-- Select which sensor to display -->
<h2>Select Sensor Node</h2>

<form method="GET" action="index.php">
    <label for="node">Choose a sensor:</label>

    <select name="node" id="node"
            onchange="this.form.submit()">

        <?php foreach ($nodes as $node): ?>
            <option
                value="<?= htmlspecialchars($node) ?>"
                <?= $node === $selected_node ? 'selected' : '' ?>
            >
                <?= htmlspecialchars($node) ?>
            </option>
        <?php endforeach; ?>

    </select>
</form>

<!-- Display average readings for node_1 -->

<h2>
    Sensor Statistics -
    <?= htmlspecialchars($selected_node) ?>
</h2>

<p>
    Average Temperature:
    <strong>
        <?= number_format((float)$averages['avg_temperature'], 2) ?>
        &deg;C
    </strong>
</p>

<p>
    Average Humidity:
    <strong>
        <?= number_format((float)$averages['avg_humidity'], 2) ?>
        %
    </strong>
</p>

<!-- Maximum temperature for node_1 -->
<p>
    Maximum Temperature:
    <strong>
        <?= number_format((float)$averages['max_temperature'], 2) ?>
        &deg;C
    </strong>
</p>

<!-- Minimum humidity for node_1 -->
<p>
    Minimum Humidity:
    <strong>
        <?= number_format((float)$averages['min_humidity'], 2) ?>
        %
    </strong>
</p>

<!-- Temperature graph for node_1 -->

<h2>
    <?= htmlspecialchars($selected_node) ?> - Temperature
</h2>


<!-- Larger temperature graph -->
<div style="
    width: 100%;
    max-width: 1200px;
    height: 500px;
    margin: 25px 0;
    padding: 20px;
    background-color: white;
    box-sizing: border-box;
">
    <canvas id="temperatureChart"></canvas>
</div>


<!-- Load Chart.js -->
<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>

<script>
// Sensor data retrieved from PHP/MySQL
const chartTimes = <?= json_encode($chart_times) ?>;
const chartTemperatures = <?= json_encode($chart_temperatures) ?>;

// Create the temperature graph
const ctx = document.getElementById('temperatureChart');

new Chart(ctx, {
    type: 'line',

    data: {
        labels: chartTimes,
        
        datasets: [{
            label: 'Temperature (C)',
            data: chartTemperatures,
        
            // Green line and light green background
            backgroundColor: 'rgba(0, 180, 0, 0.2)',
            borderColor: 'rgba(0, 180, 0, 1)',
        
            borderWidth: 3,
            pointBackgroundColor: 'rgba(0, 180, 0, 1)',
            pointRadius: 5,
            tension: 0.2,
            fill: false
        }]

    },

    
    
    options: {
        responsive: true,
        maintainAspectRatio: false,
        devicePixelRatio: 2,
    
        plugins: {
            title: {
                display: true,
                text: <?= json_encode('Sensor ' . $selected_node . ' - Temperature') ?>,
                font: {
                    size: 26,
                    weight: 'bold'
                },
                padding: 20
            },
    
            legend: {
                labels: {
                    font: {
                        size: 18
                    }
                }
            }
        },
    
        scales: {
            x: {
                title: {
                    display: true,
                    text: 'Time',
                    font: {
                        size: 20,
                        weight: 'bold'
                    }
                },
    
                ticks: {
                    font: {
                        size: 16
                    },
                    maxRotation: 45,
                    minRotation: 45
                }
            },
    
            y: {
                beginAtZero: true,
    
                title: {
                    display: true,
                    text: 'Temperature (C)',
                    font: {
                        size: 20,
                        weight: 'bold'
                    }
                },
    
                ticks: {
                    font: {
                        size: 16
                    }
                }
            }
        }
    }


});
</script>

</body>
</html>

<?php
// Close database connection
$conn->close();
?>
