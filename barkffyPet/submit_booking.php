<?php
include 'db_connection.php';
header('Content-Type: application/json');

// ✅ Step 1: Decode JSON input from JS fetch()
$input = json_decode(file_get_contents('php://input'), true);

// ✅ Step 2: Validate and assign variables safely
if (!$input || !isset($input['user_id'], $input['service_id'], $input['booking_date'], $input['booking_time'])) {
    echo json_encode(["error" => "Missing or invalid fields."]);
    exit;
}

$user_id = intval($input['user_id']);
$pet_id = !empty($input['pet_id']) ? intval($input['pet_id']) : null; // ✅ Allow NULL pet_id safely, temporary
$service_id = intval($input['service_id']);
$booking_date = $input['booking_date'];
$booking_time = $input['booking_time'];
$status = 'pending';
$notes = $input['notes'] ?? null;

// ✅ Step 3: Get service duration
$q = $conn->prepare("SELECT duration_minutes FROM services WHERE service_id = ?");
$q->bind_param("i", $service_id);
$q->execute();
$r = $q->get_result();
if ($r->num_rows === 0) {
    echo json_encode(["error" => "Invalid service."]);
    exit;
}
$service = $r->fetch_assoc();
$duration = intval($service['duration_minutes']);

// ✅ Step 4: Compute start & end time
$start_time = $booking_time;
$end_time = date('H:i:s', strtotime("$booking_time +{$duration} minutes"));

// ✅ Step 5: Check for overlap
$check = $conn->prepare("
    SELECT * FROM bookings
    WHERE service_id = ? AND booking_date = ?
    AND ((start_time < ? AND end_time > ?) OR (start_time >= ? AND start_time < ?))
");
$check->bind_param("isssss", $service_id, $booking_date, $end_time, $start_time, $start_time, $end_time);
$check->execute();
$res = $check->get_result();

if ($res->num_rows > 0) {
    echo json_encode(["error" => "Time slot overlaps with an existing booking."]);
    exit;
}

// ✅ Step 6: Insert booking
$sql = "INSERT INTO bookings (user_id, pet_id, service_id, booking_date, start_time, end_time, booking_status, notes)
        VALUES (?, ?, ?, ?, ?, ?, ?, ?)";
$stmt = $conn->prepare($sql);
$stmt->bind_param("iiisssss", $user_id, $pet_id, $service_id, $booking_date, $start_time, $end_time, $status, $notes);

if ($stmt->execute()) {
    echo json_encode(["success" => "Booking submitted successfully!"]);
} else {
    echo json_encode([
        "error" => "Failed to create booking.",
        "stmt_error" => $stmt->error,
        "conn_error" => $conn->error
    ]);
}

?>
