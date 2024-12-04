<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Europe/Vilnius');

if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vartotojas') {
    header("Location: login.php");
    exit();
}

$userId = $_SESSION['user_id'];
$success = '';
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $amount = $_POST['amount'];

    // Check if the amount is a valid number and within the allowed range
    if (is_numeric($amount) && $amount > 0 && $amount <= 10000) {
        // Update the user's balance
        $stmt = $pdo->prepare("UPDATE vartotojai SET pinigai = pinigai + ? WHERE id = ?");
        $stmt->execute([$amount, $userId]);
        $success = "Jūsų balansas papildytas " . number_format($amount, 2) . " EUR.";
    } else {
        $error = "Įveskite teisingą sumą (daugiausia 10 000 EUR).";
    }
}

// Fetch the user's balance from the database
$stmt = $pdo->prepare("SELECT pinigai FROM vartotojai WHERE id = ?");
$stmt->execute([$userId]);
$userBalance = $stmt->fetchColumn();
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Papildyti Balansą</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Make the footer stick to the bottom */
        body {
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include 'navbar.php'; ?>

<div class="container content">
    <h2 class="text-center mt-5 mb-4">Papildyti Jūsų Balansą</h2>

    <?php if ($success): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php elseif ($error): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php endif; ?>

    <form method="POST" action="top_up.php" class="w-50 mx-auto">
        <div class="form-group">
            <label for="amount">Įveskite sumą, kurią norite papildyti (EUR):</label>
            <input type="number" class="form-control" id="amount" name="amount" step="0.01" min="0.01" max="10000" required>
        </div>
        <button type="submit" class="btn btn-primary btn-block">Papildyti</button>
    </form>
</div>

<!-- Footer -->
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
<script>
    document.addEventListener("DOMContentLoaded", function() {
        if (sessionStorage.getItem('scrollPosition') !== null) {
            window.scrollTo(0, sessionStorage.getItem('scrollPosition'));
        }
    });

    window.addEventListener('beforeunload', function() {
        sessionStorage.setItem('scrollPosition', window.scrollY);
    });
</script>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
