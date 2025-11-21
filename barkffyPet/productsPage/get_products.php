<?php
include '../db_connection.php';
header('Content-Type: application/json');

// Optional filters (from URL query params)
$category_id = isset($_GET['category_id']) ? intval($_GET['category_id']) : 0;
$subcategory = isset($_GET['subcategory']) ? $_GET['subcategory'] : null;

// Base SQL with join to categories
$sql = "
SELECT 
    p.*, 
    c.name AS category_name,
    c.parent_id,
    p.image_url,
    parent.name AS parent_name
FROM products p
JOIN categories c ON p.category_id = c.category_id
LEFT JOIN categories parent ON c.parent_id = parent.category_id
";

// 🧠 Filter by category or subcategory if requested
if ($category_id > 0) {
    // Show all products for this category OR its subcategories
    $sql .= " AND (c.category_id = $category_id OR c.parent_id = $category_id)";
}

if ($subcategory) {
    // e.g. subcategory=food or subcategory=accessories
    $sql .= " AND LOWER(c.name) = '" . $conn->real_escape_string(strtolower($subcategory)) . "'";
}

// Run the query
$result = $conn->query($sql);

$products = [];
while ($row = $result->fetch_assoc()) {
    $products[] = $row;
}

echo json_encode($products);
?>
