<?php
include '../db_connection.php';
header('Content-Type: application/json');

// DELETE PRODUCT
if (isset($_POST['delete_id'])) {
    $id = intval($_POST['delete_id']);
    $stmt = $conn->prepare("DELETE FROM products WHERE product_id = ?");
    $stmt->bind_param("i", $id);
    $stmt->execute();

    echo json_encode(['message' => 'Product deleted successfully']);
    exit;
}

// INSERT OR UPDATE PRODUCT
$product_id = $_POST['product_id'] ?? null;
$category_id = intval($_POST['category_id']);
$name = $_POST['name'];
$price = floatval($_POST['price']);
$stock_qty = intval($_POST['stock_qty']);
$active = intval($_POST['active']);
$image_url = $_POST['image_url'] ?? null;

if ($product_id) {
    // UPDATE
    $stmt = $conn->prepare("UPDATE products SET category_id=?, name=?, price=?, stock_qty=?, active=?, image_url=? WHERE product_id=?");
    $stmt->bind_param("isdiisi", $category_id, $name, $price, $stock_qty, $active, $image_url, $product_id);
    $stmt->execute();

    echo json_encode(['message' => 'Product updated successfully']);
} else {
    // INSERT
    $stmt = $conn->prepare("INSERT INTO products (category_id, name, price, stock_qty, active, image_url) VALUES (?, ?, ?, ?, ?, ?)");
    $stmt->bind_param("isdiis", $category_id, $name, $price, $stock_qty, $active, $image_url);
    $stmt->execute();

    echo json_encode(['message' => 'New product added successfully']);
}
?>
