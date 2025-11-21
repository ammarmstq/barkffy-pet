<?php
include '../db_connection.php';
header('Content-Type: application/json');

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    echo json_encode(['error' => 'Invalid or missing product ID']);
    exit;
}

$id = intval($_GET['id']);
$stmt = $conn->prepare("SELECT * FROM products WHERE product_id = ?");
$stmt->bind_param("i", $id);
$stmt->execute();
$res = $stmt->get_result();

if ($res->num_rows === 0) {
    echo json_encode(['error' => 'Product not found']);
    exit;
}

echo json_encode($res->fetch_assoc());
?>
