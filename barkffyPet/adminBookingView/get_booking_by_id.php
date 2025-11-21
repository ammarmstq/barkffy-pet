<?php
//used to auto fill form by searching booking id
include '../db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || empty($_GET['id'])) {
    echo json_encode(['error' => 'No booking ID provided']);
    exit;
}

$id = intval($_GET['id']);
$sql = "SELECT * FROM bookings WHERE booking_id = $id";
$result = $conn->query($sql);

if ($result && $result->num_rows > 0) {
    $row = $result->fetch_assoc();
    echo json_encode($row);
} else {
    echo json_encode(['error' => 'Booking not found']);
}
?>
