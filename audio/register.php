<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Europe/Vilnius');

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = trim($_POST['username']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];

    // Basic validation
    if (empty($username) || empty($password) || empty($confirm_password)) {
        $error = "Visi laukai yra privalomi.";
    } elseif ($password !== $confirm_password) {
        $error = "Slaptažodžiai nesutampa.";
    } else {
        // Check if username already exists
        $stmt = $pdo->prepare("SELECT id FROM vartotojai WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $error = "Toks vartotojo vardas jau egzistuoja.";
        } else {
            // Hash the password
            $hashed_password = password_hash($password, PASSWORD_DEFAULT);

            // Insert new user into the database
            $stmt = $pdo->prepare("INSERT INTO vartotojai (username, slaptazodis, pinigai, role) VALUES (?, ?, 0, 'Vartotojas')");
            $stmt->execute([$username, $hashed_password]);

            // Registration successful, redirect to login page
            header("Location: login.php?register_success=1");
            exit();
        }
    }
}
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Registracija</title>
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
        <div class="row justify-content-center mt-5">
            <div class="col-md-4">
                <h2 class="text-center">Registracija</h2>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="register.php" method="POST">
                    <div class="form-group">
                        <label for="username">Vartotojo vardas</label>
                        <input type="text" class="form-control" id="username" name="username" required value="<?php echo isset($username) ? htmlspecialchars($username) : ''; ?>">
                    </div>
                    <div class="form-group">
                        <label for="password">Slaptažodis</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <div class="form-group">
                        <label for="confirm_password">Pakartokite slaptažodį</label>
                        <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Registruotis</button>
                </form>
            </div>
        </div>
    </div>
    <!-- Footer -->
    <?php include 'footer.php'; ?>

    <script src="https://code.jquery.com/jquery-3.5.1.slim.min.js"></script>
    <script>
        // Preserve scroll position
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
