<?php
include '../db_connection.php';
header('Content-Type: application/json');

$sql = "SELECT category_id, name, parent_id, description FROM categories ORDER BY category_id ASC";
$result = $conn->query($sql);

$categories = [];
while ($row = $result->fetch_assoc()) {
    $categories[] = $row;
}

echo json_encode($categories);
?>
