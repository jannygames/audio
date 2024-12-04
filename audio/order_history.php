<?php
session_start();
date_default_timezone_set('Europe/Vilnius');
// Ensure the user is logged in as Vartotojas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vartotojas') {
    header("Location: login.php");
    exit();
}

// Include the database connection
require 'db_connection.php';

// Update order statuses to 'ivykdytas' if 2 minutes have passed since approval
$userId = $_SESSION['user_id'];
$timeLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));

$updateStatusStmt = $pdo->prepare("
    UPDATE uzsakymai
    SET busena = 'ivykdytas'
    WHERE user_id = ? AND busena = 'priimtas' AND approval_time <= ?
");
$updateStatusStmt->execute([$userId, $timeLimit]);

// Fetch all orders for the logged-in user
$stmt = $pdo->prepare("
    SELECT u.id AS order_id, u.data, u.prekiu_kiekis, u.suma, u.busena, u.rezervacijos_galiojimo_data,
           p.gamintojas, p.modelis, p.kaina
    FROM uzsakymai u
    JOIN prekes p ON u.uzsakyta_preke = p.id
    WHERE u.user_id = ?
    ORDER BY u.data DESC
");
$stmt->execute([$userId]);
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT pinigai FROM vartotojai WHERE id = ?");
    $stmt->execute([$userId]);
    $userBalance = $stmt->fetchColumn();
} else {
    $userBalance = 0; // Default balance if not logged in
}
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Užsakymų Istorija</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .status-priimtas {
            background-color: #007bff; /* Blue color */
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-ivykdytas {
            background-color: #28a745; /* Green color */
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-atmestas {
            background-color: #dc3545; /* Red color */
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-pateiktas {
            background-color: #17a2b8; /* Teal color */
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-rezervuotas {
            background-color: #ffc107; /* Yellow color */
            color: #212529;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .reservation-date {
            font-size: 0.9em;
            color: #555;
        }

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

<div class="container mt-5 content">
    <h2 class="text-center mb-4">Jūsų Užsakymų Istorija</h2>

    <?php if (empty($orders)): ?>
        <p class="text-center">Jūs neturite užsakymų.</p>
    <?php else: ?>
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Užsakymo ID</th>
                    <th>Užsakymo Data</th>
                    <th>Gamintojas</th>
                    <th>Modelis</th>
                    <th>Kiekis</th>
                    <th>Vieneto Kaina</th>
                    <th>Visa Suma</th>
                    <th>Statusas</th>
                    <th>Rezervacijos Galiojimas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td><?php echo htmlspecialchars($order['data']); ?></td>
                        <td><?php echo htmlspecialchars($order['gamintojas']); ?></td>
                        <td><?php echo htmlspecialchars($order['modelis']); ?></td>
                        <td><?php echo htmlspecialchars($order['prekiu_kiekis']); ?></td>
                        <td><?php echo number_format($order['kaina'], 2); ?> EUR</td>
                        <td><?php echo number_format($order['suma'], 2); ?> EUR</td>
                        <td>
                            <div class="status-<?php echo htmlspecialchars($order['busena']); ?>">
                                <?php echo ucfirst($order['busena']); ?>
                            </div>
                        </td>
                        <td>
                            <?php
                            if ($order['busena'] === 'rezervuotas') {
                                echo '<div class="reservation-date">' . htmlspecialchars($order['rezervacijos_galiojimo_data']) . '</div>';
                            } else {
                                echo '-';
                            }
                            ?>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
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