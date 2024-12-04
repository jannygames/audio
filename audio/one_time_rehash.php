<?php
require 'db_connection.php';

// Fetch all users
$stmt = $pdo->query("SELECT id, slaptazodis FROM vartotojai");
$users = $stmt->fetchAll(PDO::FETCH_ASSOC);

foreach ($users as $user) {
    $userId = $user['id'];
    $plainPassword = $user['slaptazodis'];

    // Hash the plaintext password
    $hashedPassword = password_hash($plainPassword, PASSWORD_DEFAULT);

    // Update the user's password
    $updateStmt = $pdo->prepare("UPDATE vartotojai SET slaptazodis = ? WHERE id = ?");
    $updateStmt->execute([$hashedPassword, $userId]);
}

echo "Passwords have been hashed.";