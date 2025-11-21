<?php
// =============================
// get_available_times.php
// =============================

// Database connection
$host = "localhost";
$user = "root";
$pass = "";
$dbname = "barkffy_pet_db";

$conn = new mysqli($host, $user, $pass, $dbname);

if ($conn->connect_error) {
    die(json_encode(["error" => "Database connection failed: " . $conn->connect_error]));
}

// Get data from frontend (via AJAX)
$service_id = isset($_GET['service_id']) ? intval($_GET['service_id']) : 0;
$booking_date = isset($_GET['date']) ? $_GET['date'] : '';

if (!$service_id || !$booking_date) {
    echo json_encode(["error" => "Missing required parameters."]);
    exit;
}

// 1️⃣ Get service duration
$sql_service = "SELECT duration_minutes FROM services WHERE service_id = ?";
$stmt_service = $conn->prepare($sql_service);
$stmt_service->bind_param("i", $service_id);
$stmt_service->execute();
$result_service = $stmt_service->get_result();

if ($result_service->num_rows === 0) {
    echo json_encode(["error" => "Service not found."]);
    exit;
}

$service = $result_service->fetch_assoc();
$duration = (int)$service['duration_minutes'];

// 2️⃣ Get existing bookings for the date
$sql_bookings = "
    SELECT start_time, end_time 
    FROM bookings 
    WHERE booking_date = ? AND booking_status IN ('pending', 'confirmed')
";
$stmt_bookings = $conn->prepare($sql_bookings);
$stmt_bookings->bind_param("s", $booking_date);
$stmt_bookings->execute();
$result_bookings = $stmt_bookings->get_result();

$booked_slots = [];
while ($row = $result_bookings->fetch_assoc()) {
    $booked_slots[] = [
        "start" => $row['start_time'],
        "end"   => $row['end_time']
    ];
}

// 3️⃣ Define working hours (e.g. 9:00 AM - 6:00 PM)
$open_time = new DateTime("09:00");
$close_time = new DateTime("18:00");
$interval = new DateInterval("PT30M"); // 30-minute blocks

$available = [];

for ($time = clone $open_time; $time < $close_time; $time->add($interval)) {
    $slot_start = clone $time;
    $slot_end = (clone $slot_start)->add(new DateInterval("PT" . $duration . "M"));

    // Skip if slot goes past closing time
    if ($slot_end > $close_time) {
        break;
    }

    $conflict = false;

    // Check overlap with existing bookings
    foreach ($booked_slots as $b) {
        $booked_start = new DateTime($b['start']);
        $booked_end = new DateTime($b['end']);

        // Overlap condition
        if ($slot_start < $booked_end && $slot_end > $booked_start) {
            $conflict = true;
            break;
        }
    }

    if (!$conflict) {
        $available[] = [
            "start" => $slot_start->format("H:i"),
            "end"   => $slot_end->format("H:i")
        ];
    }
}

// 4️⃣ Return JSON
header("Content-Type: application/json");
echo json_encode($available);
?>
