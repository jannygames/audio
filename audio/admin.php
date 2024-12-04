<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
if (!isset($_SESSION['username']) || $_SESSION['role'] !== 'Administratorius') {
    header("Location: login.php");
    exit();
}

// Database connection settings
require 'db_connection.php';

// Fetch all users from the database
$stmt = $pdo->query("SELECT id, username, el_pastas, role FROM vartotojai");
$users = $stmt->fetchAll();

// Handle new user form submission
if ($_SERVER['REQUEST_METHOD'] == 'POST' && isset($_POST['action']) && $_POST['action'] == 'add_user') {
    $username = $_POST['username'];
    $email = $_POST['email'];
    $password = $_POST['password'];
    $role = $_POST['role'];

    // Insert the new user into the database
    $stmt = $pdo->prepare("INSERT INTO vartotojai (username, el_pastas, slaptazodis, role, pinigai) VALUES (:username, :email, :password, :role, 0)");
    $stmt->execute([
        'username' => $username,
        'email' => $email,
        'password' => $password,
        'role' => $role
    ]);

    // Refresh the page to see the new user in the table
    header("Location: admin.php");
    exit();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>Admin Dashboard</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
</head>
<body>
    <!-- Navbar -->
    <nav class="navbar navbar-expand-lg navbar-light bg-light">
        <a class="navbar-brand" href="#">Welcome, <?php echo htmlspecialchars($_SESSION['username']); ?></a>
        <div class="collapse navbar-collapse justify-content-end">
            <ul class="navbar-nav">
                <li class="nav-item">
                    <a class="nav-link btn btn-outline-primary" href="admin.php">Admin Dashboard</a>
                </li>
                <li class="nav-item ml-2">
                    <a class="nav-link btn btn-danger" href="logout.php">Logout</a>
                </li>
            </ul>
        </div>
    </nav>

    <div class="container mt-5">
        <!-- Users Table -->
        <h2>All Users</h2>
        <form id="bulkDeleteForm" action="delete_user.php" method="POST">
            <input type="hidden" name="action" value="bulk_delete">
            <button type="submit" class="btn btn-danger mb-3" onclick="return confirm('Are you sure you want to delete the selected users?');">Delete Selected</button>
            <table class="table table-bordered">
                <thead class="thead-dark">
                    <tr>
                        <th><input type="checkbox" id="selectAll"></th>
                        <th>ID</th>
                        <th>Username</th>
                        <th>Email</th>
                        <th>Role</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($users as $user) : ?>
                        <tr>
                            <td><input type="checkbox" name="selected_users[]" value="<?php echo $user['id']; ?>"></td>
                            <td><?php echo htmlspecialchars($user['id']); ?></td>
                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                            <td><?php echo htmlspecialchars($user['el_pastas']); ?></td>
                            <td><?php echo htmlspecialchars($user['role']); ?></td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>
        </form>

        <!-- Add New User Form -->
        <h2 class="mt-5">Add New User</h2>
        <form action="admin.php" method="POST">
            <input type="hidden" name="action" value="add_user">
            <div class="form-group">
                <label for="username">Username</label>
                <input type="text" class="form-control" id="username" name="username" required>
            </div>
            <div class="form-group">
                <label for="email">Email</label>
                <input type="email" class="form-control" id="email" name="email" required>
            </div>
            <div class="form-group">
                <label for="password">Password</label>
                <input type="password" class="form-control" id="password" name="password" required>
            </div>
            <div class="form-group">
                <label for="role">Role</label>
                <select class="form-control" id="role" name="role" required>
                    <option value="Administratorius">Administratorius</option>
                    <option value="Vartotojas">Vartotojas</option>
                    <option value="Vadybininkas">Vadybininkas</option>
                </select>
            </div>
            <button type="submit" class="btn btn-success">Add User</button>
        </form>
    </div>
    <?php include 'footer.php'; ?>
    
    <script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@4.5.2/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Select/Deselect All Checkboxes
        document.getElementById('selectAll').addEventListener('click', function() {
            let checkboxes = document.querySelectorAll('input[name="selected_users[]"]');
            checkboxes.forEach(checkbox => checkbox.checked = this.checked);
        });
    </script>
    
</body>
</html>
