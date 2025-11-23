<?php
// create_order.php
header("Content-Type: application/json");
session_start();
include "db_connection.php";

// 1. Read JSON data
$input = json_decode(file_get_contents("php://input"), true);

if (!$input || !isset($input["cart"]) || !isset($input["user_id"])) {
    echo json_encode(["error" => "Invalid order payload"]);
    exit;
}

$user_id      = intval($input["user_id"]);
$cart_items   = $input["cart"];
$address      = $input["shipping_address"] ?? null;

// 2. Compute total_amount
$total_amount = 0;
foreach ($cart_items as $item) {
    $total_amount += $item["qty"] * $item["price"];
}

// 3. Insert into `orders`
$order_sql = "
    INSERT INTO orders (user_id, total_amount, payment_status, fulfillment_status, shipping_address)
    VALUES (?, ?, 'pending', 'processing', ?)
";

$stmt = $conn->prepare($order_sql);
$stmt->bind_param("ids", $user_id, $total_amount, $address);

if (!$stmt->execute()) {
    echo json_encode(["error" => "Failed to create order"]);
    exit;
}

$order_id = $stmt->insert_id;

// 4. Insert items into order_items
$item_sql = "
    INSERT INTO order_items (order_id, product_id, quantity, unit_price, subtotal)
    VALUES (?, ?, ?, ?, ?)
";
$item_stmt = $conn->prepare($item_sql);

foreach ($cart_items as $item) {

    $product_id = intval($item["product_id"]);
    $qty        = intval($item["qty"]);
    $unit_price = floatval($item["price"]);
    $subtotal   = $qty * $unit_price;

    $item_stmt->bind_param(
        "iiidd",
        $order_id,
        $product_id,
        $qty,
        $unit_price,
        $subtotal
    );

    $item_stmt->execute();
}

// 5. Return success
echo json_encode([
    "success"   => true,
    "order_id"  => $order_id,
    "total"     => $total_amount
]);
