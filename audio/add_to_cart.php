<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
// Check if user is logged in as Vartotojas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vartotojas') {
    header("Location: login.php");
    exit();
}

require 'db_connection.php'; // Include database connection

$itemId = $_POST['item_id'] ?? null;
$quantity = $_POST['quantity'] ?? 1;
$userId = $_SESSION['user_id'];

if ($itemId && $quantity > 0) {
    // Update the session cart
    if (isset($_SESSION['cart'][$itemId])) {
        $_SESSION['cart'][$itemId] += $quantity;
    } else {
        $_SESSION['cart'][$itemId] = $quantity;
    }

    // Insert or update the cart item in the database
    $stmt = $pdo->prepare("INSERT INTO krepselis (user_id, item_id, quantity) VALUES (?, ?, ?)
                           ON DUPLICATE KEY UPDATE quantity = quantity + VALUES(quantity)");
    $stmt->execute([$userId, $itemId, $quantity]);

    // Redirect back with success message
    header("Location: index.php?cart_success=1");
    exit();
} else {
    header("Location: index.php?cart_error=1");
    exit();
}
