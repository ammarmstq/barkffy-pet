<?php
include '../db_connection.php';
header('Content-Type: application/json');

// DELETE
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $conn->query("DELETE FROM bookings WHERE booking_id = $id");
    echo json_encode(['message' => 'Booking deleted successfully']);
    exit;
}

$id = $_POST['booking_id'] ?? null;
$user_id = intval($_POST['user_id']);
$pet_id = !empty($_POST['pet_id']) ? intval($_POST['pet_id']) : NULL;
$service_id = intval($_POST['service_id']);
$date = $_POST['booking_date'];
$start = $_POST['start_time'];
$end = !empty($_POST['end_time']) ? $_POST['end_time'] : null;
$status = $_POST['booking_status'];
$notes = $_POST['notes'];

if ($id) {
    error_log("Booking ID received: " . $id);
    $stmt = $conn->prepare("UPDATE bookings SET user_id=?, pet_id=?, service_id=?, booking_date=?, start_time=?, end_time=?, booking_status=?, notes=?, modified_by=? WHERE booking_id=?");
    $modified_by = 1; //$_SESSION['user_id'];
    $stmt->bind_param("iiisssssii", $user_id, $pet_id, $service_id, $date, $start, $end, $status, $notes, $modified_by, $id);
    $stmt->execute();
    echo json_encode(['message' => 'Booking updated successfully']);
} else {
    $stmt = $conn->prepare("INSERT INTO bookings (user_id, pet_id, service_id, booking_date, start_time, end_time, booking_status, notes, modified_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)");
    $modified_by = 1; // $_SESSION['user_id'];
    $stmt->bind_param("iiisssssi", $user_id, $pet_id, $service_id, $date, $start, $end, $status, $notes, $modified_by);
    $stmt->execute();
    echo json_encode(['message' => 'New booking added successfully']);
}
?>
