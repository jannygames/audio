<nav class="navbar navbar-expand-lg navbar-dark bg-dark">
    <a class="navbar-brand" href="index.php">Audio Pasaulis</a>
    <div class="collapse navbar-collapse justify-content-end">
        <ul class="navbar-nav">
            <?php if (isset($_SESSION['username'])): ?>
                <li class="navbar-text mr-3">Sveiki, <?php echo htmlspecialchars($_SESSION['username']); ?></li>
                <?php if ($_SESSION['role'] === 'Vartotojas'): ?>
                    <li class="navbar-text">Balansas: <?php echo number_format($userBalance, 2); ?> EUR</li>
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ml-2" href="top_up.php">Papildyti</a>
                    </li>
                    <!-- New Link for Order History -->
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ml-2" href="order_history.php">Užsakymų Istorija</a>
                    </li>
                    <!-- Existing Cart Link -->
                    <li class="nav-item">
                        <a class="nav-link btn btn-outline-light ml-2" href="cart.php">Krepšelis</a>
                    </li>
                <?php endif; ?>
                <!-- Other roles and logout link -->
                <?php if ($_SESSION['role'] === 'Vadybininkas'): ?>
                    <li class="nav-item">
                        <a class="nav-link btn btn-info mr-2" href="review_orders.php">Peržiūrėti Užsakymus</a>
                    </li>
                <?php endif; ?>
                <li class="nav-item">
                    <a class="nav-link btn btn-danger ml-2" href="logout.php">Atsijungti</a>
                </li>
            <?php else: ?>
                <li class="nav-item">
                    <a class="nav-link btn btn-primary" href="login.php">Prisijungti</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link btn btn-success" href="register.php">Registruotis</a>
                </li>
            <?php endif; ?>
        </ul>
    </div>
</nav>