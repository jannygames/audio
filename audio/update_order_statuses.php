<?php
// update_order_statuses.php
date_default_timezone_set('Europe/Vilnius');
// Ensure the database connection is available
if (!isset($pdo)) {
    require 'db_connection.php';
}

// Update order statuses to 'ivykdytas' if 2 minutes have passed since approval
$timeLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));

$updateStatusStmt = $pdo->prepare("
    UPDATE uzsakymai
    SET busena = 'ivykdytas'
    WHERE busena = 'priimtas' AND approval_time <= ?
");
$updateStatusStmt->execute([$timeLimit]);
?>
