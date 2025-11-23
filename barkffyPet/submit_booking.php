<?php
// submit_booking.php  (FINAL MERGED VERSION)

// 1. PHPMailer (Brevo) setup
use PHPMailer\PHPMailer\PHPMailer;
use PHPMailer\PHPMailer\Exception;

require __DIR__ . '/PHPMailer/src/Exception.php';
require __DIR__ . '/PHPMailer/src/PHPMailer.php';
require __DIR__ . '/PHPMailer/src/SMTP.php';

// 2. DB connection
include 'db_connection.php';

header('Content-Type: application/json');

try {
    // 3. Read & decode JSON from booking.js
    $input = json_decode(file_get_contents('php://input'), true);

    if (
        !$input ||
        !isset($input['user_id'], $input['service_id'], $input['booking_date'], $input['booking_time'])
    ) {
        echo json_encode(["error" => "Missing or invalid fields."]);
        exit;
    }

    // ----- BASIC FIELDS (original logic) -----
    $user_id      = (int)$input['user_id'];
    $pet_id       = !empty($input['pet_id']) ? (int)$input['pet_id'] : null; // still nullable
    $service_id   = (int)$input['service_id'];
    $booking_date = $input['booking_date']; // 'YYYY-MM-DD'
    $booking_time = $input['booking_time']; // 'HH:MM'
    $notes        = $input['notes'] ?? null;

    // Status as requested
    $status = 'confirmed';

    // ----- NEW CUSTOMER FIELDS -----
    $customer_name  = trim($input['customer_name'] ?? '');
    $customer_email = trim($input['customer_email'] ?? '');
    $customer_phone = trim($input['customer_phone'] ?? '');

    if (empty($customer_email)) {
        echo json_encode(["error" => "Email is required."]);
        exit;
    }

    // 4. Get service duration & price (like old code but expanded)
    $q = $conn->prepare("SELECT duration_minutes, price, name FROM services WHERE service_id = ?");
    $q->bind_param("i", $service_id);
    $q->execute();
    $r = $q->get_result();

    if ($r->num_rows === 0) {
        echo json_encode(["error" => "Invalid service."]);
        exit;
    }

    $service          = $r->fetch_assoc();
    $duration_minutes = (int)$service['duration_minutes'];
    $service_price    = (float)$service['price'];
    $service_name     = $service['name'];

    // 5. Compute start_time & end_time (original logic)
    $start_time = $booking_time; // stored as 'HH:MM' (MySQL TIME will accept it)
    $end_time   = date('H:i:s', strtotime("$booking_time +{$duration_minutes} minutes"));

    // 6. Check for overlapping bookings (original logic)
    $check = $conn->prepare("
        SELECT * FROM bookings
        WHERE service_id = ?
          AND booking_date = ?
          AND (
                (start_time < ? AND end_time > ?)
             OR (start_time >= ? AND start_time < ?)
          )
    ");
    $check->bind_param(
        "isssss",
        $service_id,
        $booking_date,
        $end_time,
        $start_time,
        $start_time,
        $end_time
    );
    $check->execute();
    $res = $check->get_result();

    if ($res->num_rows > 0) {
        echo json_encode(["error" => "Time slot overlaps with an existing booking."]);
        exit;
    }

    // 7. Insert booking (adapted to your CURRENT bookings table)
    //
    // bookings columns (from screenshot):
    //  booking_id (AI),
    //  user_id, pet_id, service_id,
    //  customer_name, customer_email, customer_phone,
    //  reminder_sent (default 0),
    //  booking_date, start_time, end_time,
    //  booking_status, notes, modified_by,
    //  created_at, updated_at
    //
    // We will let reminder_sent = DEFAULT 0, created_at/updated_at auto.

    $modified_by = null;

    $sql = "INSERT INTO bookings (
                user_id,
                pet_id,
                service_id,
                customer_name,
                customer_email,
                customer_phone,
                booking_date,
                start_time,
                end_time,
                booking_status,
                notes,
                modified_by
            )
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";

    $stmt = $conn->prepare($sql);

    $stmt->bind_param(
        "iiissssssssi",
        $user_id,
        $pet_id,
        $service_id,
        $customer_name,
        $customer_email,
        $customer_phone,
        $booking_date,
        $start_time,
        $end_time,
        $status,
        $notes,
        $modified_by
    );

    if (!$stmt->execute()) {
        echo json_encode([
            "error"      => "Failed to create booking.",
            "stmt_error" => $stmt->error,
            "conn_error" => $conn->error
        ]);
        exit;
    }

    $booking_id = $stmt->insert_id;

    // 8. Generate QR code via API & save to /uploads/qrcodes/
    $qrData   = "BarkffyBooking:" . $booking_id;
    $qrApiUrl = 'https://api.qrserver.com/v1/create-qr-code/?size=250x250&data=' . urlencode($qrData);

    $qrDir = __DIR__ . '/uploads/qrcodes';
    if (!is_dir($qrDir)) {
        mkdir($qrDir, 0777, true);
    }

    $qrFilename    = 'booking_' . $booking_id . '.png';
    $qrFullPath    = $qrDir . '/' . $qrFilename;
    $qrImage       = @file_get_contents($qrApiUrl);
    if ($qrImage !== false) {
        file_put_contents($qrFullPath, $qrImage);
    }

    // relative path for browser
    $qrWebPath = 'uploads/qrcodes/' . $qrFilename;

    // 9. Build email content
    $subject = "Barkffy Pet Booking Confirmation #{$booking_id}";
    $baseUrl = 'http://localhost/barkffyPet'; // adjust if project path changes

    $message = "
    <html>
      <body>
        <h2>Thank you for booking with Barkffy Pet!</h2>
        <p>Your booking has been received. Here are the details:</p>
        <ul>
          <li><strong>Booking ID:</strong> {$booking_id}</li>
          <li><strong>Name:</strong> " . htmlspecialchars($customer_name, ENT_QUOTES, 'UTF-8') . "</li>
          <li><strong>Service:</strong> " . htmlspecialchars($service_name, ENT_QUOTES, 'UTF-8') . "</li>
          <li><strong>Date:</strong> {$booking_date}</li>
          <li><strong>Time:</strong> {$start_time}</li>
          <li><strong>Duration:</strong> {$duration_minutes} mins</li>
        </ul>
        <p>Please show this QR code at the store:</p>
        <p><img src=\"{$baseUrl}/{$qrWebPath}\" alt=\"Booking QR\" /></p>
        <p>We look forward to seeing you and your pet! 🐶🐱</p>
      </body>
    </html>
    ";

    // 10. Send email via Brevo SMTP (PHPMailer)
    // 10. Send email using Brevo API (Recommended — works with Gmail sender)

    $apiKey = "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";  // paste your Brevo API key

    $emailPayload = [
        "sender" => [
            "email" => "ammarmustaqiim555work@gmail.com",
            "name"  => "Barkffy Pet"
        ],
        "to" => [
            ["email" => $customer_email]
        ],
        "subject" => $subject,
        "htmlContent" => $message
    ];

    $curl = curl_init();

    curl_setopt_array($curl, [
        CURLOPT_URL => "https://api.brevo.com/v3/smtp/email",
        CURLOPT_RETURNTRANSFER => true,
        CURLOPT_POST => true,
        CURLOPT_HTTPHEADER => [
            "api-key: $apiKey",
            "Content-Type: application/json",
            "accept: application/json"
        ],
        CURLOPT_POSTFIELDS => json_encode($emailPayload)
    ]);

    $response = curl_exec($curl);
    $curlError = curl_error($curl);
    curl_close($curl);

    // Log failures but don't break the booking flow
    if ($curlError) {
        error_log("Brevo API Email Error: " . $curlError);
    } else {
        error_log("Brevo API Email Response: " . $response);
    }


    // 11. JSON response for booking.js popup
    echo json_encode([
        'success'          => true,
        'message'          => 'Booking submitted successfully!',
        'booking_id'       => $booking_id,
        'qr_path'          => $qrWebPath,
        'service_name'     => $service_name,
        'service_price'    => $service_price,
        'service_duration' => $duration_minutes,
        'booking_date'     => $booking_date,
        'booking_time'     => $start_time,
        'customer_name'    => $customer_name,
        'email_response'    => $response ?? null,
        'email_error'       => $curlError ?? null
    ]);

} catch (Exception $e) {
    echo json_encode(['error' => 'Server error: ' . $e->getMessage()]);
}
