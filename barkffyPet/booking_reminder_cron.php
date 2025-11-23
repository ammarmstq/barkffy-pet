<?php
// booking_reminder_cron.php
include 'db_connection.php';

date_default_timezone_set('Asia/Kuala_Lumpur'); 

$now = new DateTime();
$from = clone $now;
$to   = clone $now;

// We want bookings about 24 hours from now, give a 1-hour window
$from->modify('+24 hours');
$to->modify('+25 hours');

$fromStr = $from->format('Y-m-d H:i:s');
$toStr   = $to->format('Y-m-d H:i:s');

// Select bookings needing reminder
$sql = "
  SELECT 
    b.booking_id,
    b.booking_date,
    b.start_time,
    b.customer_name,
    b.customer_email,
    b.service_id,
    b.reminder_sent,
    s.name AS service_name,
    s.duration_minutes,
    s.price
  FROM bookings b
  JOIN services s ON b.service_id = s.service_id
  WHERE 
    b.reminder_sent = 0
    AND TIMESTAMP(b.booking_date, b.start_time) BETWEEN ? AND ?
    AND b.customer_email IS NOT NULL
";

$stmt = $conn->prepare($sql);
$stmt->bind_param("ss", $fromStr, $toStr);
$stmt->execute();
$result = $stmt->get_result();

$baseUrl = 'http://localhost/barkffyPet';

while ($row = $result->fetch_assoc()) {
    $booking_id    = $row['booking_id'];
    $booking_date  = $row['booking_date'];
    $start_time    = $row['start_time'];
    $customer_name = $row['customer_name'];
    $email         = $row['customer_email'];
    $service_name  = $row['service_name'];
    $duration      = $row['duration_minutes'];
    $price         = $row['price'];

    // QR path (same pattern as submit_booking.php)
    $qrWebPath = "uploads/qrcodes/booking_{$booking_id}.png";

    $subject = "Reminder: Barkffy Pet Booking Tomorrow #$booking_id";

    $message = "
    <html>
      <body>
        <h2>Hi {$customer_name},</h2>
        <p>This is a friendly reminder that you have a booking with Barkffy Pet in 24 hours.</p>
        <ul>
          <li><strong>Booking ID:</strong> {$booking_id}</li>
          <li><strong>Service:</strong> {$service_name}</li>
          <li><strong>Date:</strong> {$booking_date}</li>
          <li><strong>Time:</strong> {$start_time}</li>
          <li><strong>Duration:</strong> {$duration} mins</li>
          <li><strong>Price:</strong> RM " . number_format($price, 2) . "</li>
        </ul>
        <p>Please show this QR code at the store:</p>
        <p><img src=\"{$baseUrl}/{$qrWebPath}\" alt=\"Booking QR\"></p>
        <p>See you soon! 🐾</p>
      </body>
    </html>
    ";

    $headers  = "MIME-Version: 1.0\r\n";
    $headers .= "Content-type:text/html;charset=UTF-8\r\n";
    $headers .= "From: Barkffy Pet <no-reply@barkffy.local>\r\n";

    @mail($email, $subject, $message, $headers);

    // Mark as reminded
    $update = $conn->prepare("UPDATE bookings SET reminder_sent = 1 WHERE booking_id = ?");
    $update->bind_param("i", $booking_id);
    $update->execute();
}

echo "Reminder script completed at " . date('Y-m-d H:i:s');
