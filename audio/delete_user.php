<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administratorius') {
    header("Location: login.php");
    exit();
}

// Database connection settings
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] === 'bulk_delete') {
    // Check if any users were selected
    if (!empty($_POST['selected_users'])) {
        $selectedUsers = $_POST['selected_users'];

        // Convert selected users array to a comma-separated list for SQL IN clause
        $placeholders = implode(',', array_fill(0, count($selectedUsers), '?'));
        $stmt = $pdo->prepare("DELETE FROM vartotojai WHERE id IN ($placeholders)");
        $stmt->execute($selectedUsers);
    }
}

// Redirect back to admin page
header("Location: admin.php");
exit();
