<?php
include '../db_connection.php';
header('Content-Type: application/json');

error_reporting(E_ALL);
ini_set('display_errors', 1);

$sql = "SELECT 
            b.booking_id,
            b.user_id,
            b.pet_id,
            b.service_id,
            b.booking_date,
            b.start_time,
            b.end_time,
            b.booking_status,
            b.notes,
            s.name AS service_name,
            u.username
        FROM bookings b
        JOIN services s ON b.service_id = s.service_id
        JOIN users u ON b.user_id = u.user_id";

$res = $conn->query($sql);

$events = [];

if ($res && $res->num_rows > 0) {
    while ($row = $res->fetch_assoc()) {
        $events[] = [
            'id' => $row['booking_id'],
            'title' => $row['service_name'] . ' - ' . ucfirst($row['booking_status']),
            'start' => $row['booking_date'] . 'T' . $row['start_time'],
            'end'   => $row['booking_date'] . 'T' . ($row['end_time'] ?? $row['start_time']),
            'extendedProps' => [
                'user_id' => $row['user_id'],
                'pet_id' => $row['pet_id'],
                'service_id' => $row['service_id'],
                'status' => $row['booking_status'],
                'notes' => $row['notes']
            ]
        ];
    }
}

echo json_encode($events);
?>
