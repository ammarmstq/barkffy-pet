<?php
include '../db_connection.php';
header('Content-Type: application/json');

$sql = "SELECT product_id, category_id, name, price, stock_qty, active FROM products ORDER BY product_id ASC";
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>
