<?php
include 'db_connection.php';

$order_id = intval($_GET['order_id'] ?? 0);
$q = $conn->prepare("SELECT * FROM orders WHERE order_id = ?");
$q->bind_param("i", $order_id);
$q->execute();
$res = $q->get_result();
$order = $res->fetch_assoc();
?>
<!DOCTYPE html>
<html>
<head>
<title>Track Order</title>
</head>
<body>
<h1>Tracking Order #<?php echo $order_id; ?></h1>

<p>Payment status: <?php echo $order['payment_status']; ?></p>
<p>Fulfillment: <?php echo $order['fulfillment_status']; ?></p>

</body>
</html>
