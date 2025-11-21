<?php
// get_services.php
header("Content-Type: application/json");

// ✅ Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barkffy_pet_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Connection failed: " . $conn->connect_error]));
}

// ✅ Fetch all active services
$sql = "SELECT service_id, name, description, price, duration_minutes, category 
        FROM services 
        WHERE active = 1 
        ORDER BY category, name";

$result = $conn->query($sql);

$services = [];

// ✅ Fetch ALL rows properly
if ($result && $result->num_rows > 0) {
    while ($row = $result->fetch_assoc()) {
        $services[] = $row;
    }
} else {
    echo json_encode(["error" => "No active services found."]);
    exit;
}

// ✅ Debug (optional - remove later)
// echo "<pre>"; print_r($services); echo "</pre>"; // Uncomment for debugging in browser

// ✅ Return JSON
echo json_encode($services);

$conn->close();
?>
