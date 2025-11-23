<?php
include 'db_connection.php';

$order_id = intval($_GET['order_id'] ?? 0);

if ($order_id === 0) {
    die("Invalid order ID.");
}

$sql = "UPDATE orders SET payment_status = 'paid' WHERE order_id = ?";
$stmt = $conn->prepare($sql);
$stmt->bind_param("i", $order_id);
$stmt->execute();
?>

<!DOCTYPE html>
<html>
<head>
<title>Payment Successful</title>
</head>
<body>
    <h1>🎉 Payment Successful!</h1>
    <p>Your order #<?php echo $order_id; ?> has been paid.</p>
    <a href="track_order.php?order_id=<?php echo $order_id; ?>">Track your order</a>
</body>
</html>
