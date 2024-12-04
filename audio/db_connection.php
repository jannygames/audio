<?php
// Database connection settings
$host = '127.0.0.1';
$db = 'projektas';
$user = 'root';
$pass = ''; // Replace with your actual database password
$charset = 'utf8mb4';
date_default_timezone_set('Europe/Vilnius');

$dsn = "mysql:host=$host;dbname=$db;charset=$charset";
$options = [
    PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
    PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    PDO::ATTR_EMULATE_PREPARES => false,
];

try {
    $pdo = new PDO($dsn, $user, $pass, $options);
    $pdo->exec("SET time_zone = '+02:00'");
} catch (PDOException $e) {
    die("Connection failed: " . $e->getMessage());
}
?>