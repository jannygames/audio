<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
require 'db_connection.php';

if ($_SERVER['REQUEST_METHOD'] == 'POST') {
    $username = $_POST['username'];
    $password = $_POST['password'];

    // Fetch the user from the database
    $stmt = $pdo->prepare("SELECT * FROM vartotojai WHERE username = ?");
    $stmt->execute([$username]);
    $user = $stmt->fetch();

    if ($user && password_verify($password, $user['slaptazodis'])) {
        // Password is correct
        $_SESSION['user_id'] = $user['id'];
        $_SESSION['username'] = $username;
        $_SESSION['role'] = $user['role'];

        // Load saved cart items from the krepselis table into session
        $_SESSION['cart'] = [];
        $stmt = $pdo->prepare("SELECT item_id, quantity FROM krepselis WHERE user_id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $cartItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

        foreach ($cartItems as $item) {
            $_SESSION['cart'][$item['item_id']] = $item['quantity'];
        }

        // Redirect based on user role
        if ($user['role'] === 'Administratorius') {
            header("Location: admin.php");
        } else {
            header("Location: index.php");
        }
        exit();
    } else {
        $error = "Neteisingas vartotojo vardas arba slaptažodis.";
    }
}
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Prisijungti</title>
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
                <h2 class="text-center">Prisijungti</h2>
                <?php if (isset($_GET['register_success'])): ?>
                    <div class="alert alert-success">Registracija sėkminga! Galite prisijungti.</div>
                <?php endif; ?>
                <?php if (isset($error)) : ?>
                    <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
                <?php endif; ?>
                <form action="login.php" method="POST">
                    <div class="form-group">
                        <label for="username">Vartotojo vardas</label>
                        <input type="text" class="form-control" id="username" name="username" required>
                    </div>
                    <div class="form-group">
                        <label for="password">Slaptažodis</label>
                        <input type="password" class="form-control" id="password" name="password" required>
                    </div>
                    <button type="submit" class="btn btn-primary btn-block">Prisijungti</button>
                </form>
                <div class="text-center mt-3">
                    <p>Neturite paskyros? <a href="register.php">Registruokitės čia</a></p>
                </div>
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
