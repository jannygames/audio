<?php
session_start();
date_default_timezone_set('Europe/Vilnius');

// Ensure the user is logged in as Vadybininkas
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'Vadybininkas') {
    header("Location: login.php");
    exit();
}

// Include the database connection
require 'db_connection.php';

// Update order statuses to 'ivykdytas' if 2 minutes have passed since approval
$timeLimit = date('Y-m-d H:i:s', strtotime('-2 minutes'));

$updateStatusStmt = $pdo->prepare("
    UPDATE uzsakymai
    SET busena = 'ivykdytas'
    WHERE busena = 'priimtas' AND approval_time <= ?
");
$updateStatusStmt->execute([$timeLimit]);

// Fetch all orders that are 'pateiktas', 'rezervuotas', 'priimtas', or 'ivykdytas'
$stmt = $pdo->prepare("
    SELECT u.id AS order_id, u.user_id, u.data, u.suma, u.busena, u.rezervacijos_galiojimo_data,
           u.uzsakyta_preke, u.prekiu_kiekis, u.approval_time,
           p.likutis AS stock_left, p.gamintojas, p.modelis,
           v.username, v.pinigai
    FROM uzsakymai u
    JOIN vartotojai v ON u.user_id = v.id
    JOIN prekes p ON u.uzsakyta_preke = p.id
    WHERE u.busena IN ('pateiktas', 'rezervuotas', 'priimtas', 'ivykdytas')
    ORDER BY u.data DESC
");
$stmt->execute();
$orders = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Handle approval or rejection of orders
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    $orderId = $_POST['order_id'];
    $action = $_POST['action'];

    // Get the order and user details
    $stmt = $pdo->prepare("
        SELECT u.user_id, u.suma, u.busena, u.uzsakyta_preke, u.prekiu_kiekis, u.rezervacijos_galiojimo_data,
               v.pinigai
        FROM uzsakymai u 
        JOIN vartotojai v ON u.user_id = v.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$orderId]);
    $order = $stmt->fetch();

    if ($order) {
        if ($action === 'approve') {
            // Fetch the stock left for the ordered item
            $stmt = $pdo->prepare("SELECT likutis FROM prekes WHERE id = ?");
            $stmt->execute([$order['uzsakyta_preke']]);
            $stockLeft = $stmt->fetchColumn();

            if ($stockLeft < $order['prekiu_kiekis']) {
                $error = "Nepakanka prekių sandėlyje užsakymui #$orderId.";
            } else {
                // Check if the reservation is still valid
                if ($order['busena'] === 'rezervuotas') {
                    if (strtotime($order['rezervacijos_galiojimo_data']) >= time()) {
                        // Reservation is valid
                        // Check if the user has enough funds
                        if ($order['pinigai'] >= $order['suma']) {
                            $pdo->beginTransaction();
                            try {
                                // Deduct funds
                                $stmt = $pdo->prepare("UPDATE vartotojai SET pinigai = pinigai - ? WHERE id = ?");
                                $stmt->execute([$order['suma'], $order['user_id']]);

                                // Mark order as "priimtas" (approved) and set approval time
                                $stmt = $pdo->prepare("UPDATE uzsakymai SET busena = 'priimtas', approval_time = NOW() WHERE id = ?");
                                $stmt->execute([$orderId]);

                                // Update stock
                                $stmt = $pdo->prepare("UPDATE prekes SET likutis = likutis - ? WHERE id = ?");
                                $stmt->execute([$order['prekiu_kiekis'], $order['uzsakyta_preke']]);

                                $pdo->commit();
                                $success = "Rezervacija #$orderId patvirtinta sėkmingai.";
                            } catch (Exception $e) {
                                $pdo->rollBack();
                                $error = "Nepavyko patvirtinti rezervacijos: " . $e->getMessage();
                            }
                        } else {
                            $error = "Vartotojas neturi pakankamai lėšų šiai rezervacijai.";
                        }
                    } else {
                        // Reservation has expired
                        $stmt = $pdo->prepare("UPDATE uzsakymai SET busena = 'atmestas' WHERE id = ?");
                        $stmt->execute([$orderId]);
                        $error = "Rezervacijos #$orderId galiojimo laikas pasibaigė.";
                    }
                } else {
                    // Handle 'pateiktas' orders (existing code)
                    // Check if the user has enough funds
                    if ($order['pinigai'] >= $order['suma']) {
                        $pdo->beginTransaction();
                        try {
                            // Deduct funds
                            $stmt = $pdo->prepare("UPDATE vartotojai SET pinigai = pinigai - ? WHERE id = ?");
                            $stmt->execute([$order['suma'], $order['user_id']]);

                            // Mark order as "priimtas" (approved) and set approval time
                            $stmt = $pdo->prepare("UPDATE uzsakymai SET busena = 'priimtas', approval_time = NOW() WHERE id = ?");
                            $stmt->execute([$orderId]);

                            // Update stock
                            $stmt = $pdo->prepare("UPDATE prekes SET likutis = likutis - ? WHERE id = ?");
                            $stmt->execute([$order['prekiu_kiekis'], $order['uzsakyta_preke']]);

                            $pdo->commit();
                            $success = "Užsakymas #$orderId patvirtintas sėkmingai.";
                        } catch (Exception $e) {
                            $pdo->rollBack();
                            $error = "Nepavyko patvirtinti užsakymo: " . $e->getMessage();
                        }
                    } else {
                        $error = "Vartotojas neturi pakankamai lėšų šiam užsakymui.";
                    }
                }
            }
        } elseif ($action === 'reject') {
            // Mark the order as Rejected
            $stmt = $pdo->prepare("UPDATE uzsakymai SET busena = 'atmestas' WHERE id = ?");
            $stmt->execute([$orderId]);

            // Optionally, return the items to stock if the order was reserved
            if ($order['busena'] === 'rezervuotas') {
                // Return items to stock
                $stmt = $pdo->prepare("UPDATE prekes SET likutis = likutis + ? WHERE id = ?");
                $stmt->execute([$order['prekiu_kiekis'], $order['uzsakyta_preke']]);
            }

            $success = "Užsakymas #$orderId atmestas sėkmingai.";
        }
    }

    // Refresh to avoid form resubmission
    header("Location: review_orders.php");
    exit();
}

// Handle fetching the selected user's order history
$userOrders = [];
if (isset($_GET['user_id']) && is_numeric($_GET['user_id'])) {
    $selectedUserId = $_GET['user_id'];

    // Initialize filter variables
    $filters = [];
    $params = [$selectedUserId]; // First parameter is user_id

    // Apply filters based on GET parameters
    if (!empty($_GET['min_price'])) {
        $filters[] = "u.suma >= ?";
        $params[] = $_GET['min_price'];
    }
    if (!empty($_GET['max_price'])) {
        $filters[] = "u.suma <= ?";
        $params[] = $_GET['max_price'];
    }
    if (!empty($_GET['busena'])) {
        // Assuming 'busena' is an array of statuses
        $placeholders = implode(',', array_fill(0, count($_GET['busena']), '?'));
        $filters[] = "u.busena IN ($placeholders)";
        $params = array_merge($params, $_GET['busena']);
    }
    if (!empty($_GET['gamintojas'])) {
        $placeholders = implode(',', array_fill(0, count($_GET['gamintojas']), '?'));
        $filters[] = "p.gamintojas IN ($placeholders)";
        $params = array_merge($params, $_GET['gamintojas']);
    }
    if (!empty($_GET['modelis'])) {
        $filters[] = "p.modelis LIKE ?";
        $params[] = '%' . $_GET['modelis'] . '%';
    }

    // Build the SQL query with filters
    $userOrdersQuery = "
        SELECT u.id AS order_id, u.data, u.suma, u.busena, u.rezervacijos_galiojimo_data,
               u.prekiu_kiekis,
               p.gamintojas, p.modelis, p.kaina
        FROM uzsakymai u
        JOIN prekes p ON u.uzsakyta_preke = p.id
        WHERE u.user_id = ?
    ";

    if (!empty($filters)) {
        $userOrdersQuery .= ' AND ' . implode(' AND ', $filters);
    }

    $userOrdersQuery .= " ORDER BY u.data DESC";

    $stmt = $pdo->prepare($userOrdersQuery);
    $stmt->execute($params);
    $userOrders = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Fetch unique statuses and manufacturers for filters
    $statusStmt = $pdo->prepare("SELECT DISTINCT busena FROM uzsakymai WHERE user_id = ?");
    $statusStmt->execute([$selectedUserId]);
    $statuses = $statusStmt->fetchAll(PDO::FETCH_COLUMN);

    $manufacturerStmt = $pdo->prepare("
        SELECT DISTINCT p.gamintojas
        FROM uzsakymai u
        JOIN prekes p ON u.uzsakyta_preke = p.id
        WHERE u.user_id = ?
    ");
    $manufacturerStmt->execute([$selectedUserId]);
    $manufacturers = $manufacturerStmt->fetchAll(PDO::FETCH_COLUMN);
}
?>
<!DOCTYPE html>
<html lang="lt">
<head>
    <!-- Existing head content -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Užsakymų Peržiūra - Vadybininkas</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .reservation-date {
            font-size: 0.9em;
            color: #555;
        }
        .status-rezervuotas {
            background-color: #ffc107;
            color: #212529;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-pateiktas {
            background-color: #17a2b8;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-priimtas {
            background-color: #007bff;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-atmestas {
            background-color: #dc3545;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        .status-ivykdytas {
            background-color: #28a745;
            color: #fff;
            padding: 5px;
            border-radius: 5px;
            text-align: center;
        }
        /* New styles for stock levels */
        .stock-insufficient {
            background-color: #f8d7da !important; /* Bootstrap danger background */
        }
        .stock-low {
            background-color: #fff3cd !important; /* Bootstrap warning background */
        }
        /* Legend styles */
        .legend {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
            border: 1px solid #000;
        }
        .legend-insufficient {
            background-color: #f8d7da !important;
        }
        .legend-low {
            background-color: #fff3cd !important;
        }
        /* Filter sidebar styles */
        .filter-sidebar {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
            margin-bottom: 20px;
        }
        .filter-sidebar h4 {
            font-weight: 600;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<!-- Existing navbar code -->
<?php include 'navbar.php'; ?>

<div class="container mt-5">
    <h2 class="text-center mb-4">Peržiūrėti Užsakymus ir Rezervacijas</h2>

    <!-- Legend -->
    <!-- Existing legend code -->
    <div class="mb-3">
        <span class="legend legend-insufficient"></span> Nepakanka prekių sandėlyje
        &nbsp;&nbsp;&nbsp;
        <span class="legend legend-low"></span> Mažas likutis (likutis mažesnis nei 10)
    </div>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (isset($success)): ?>
        <div class="alert alert-success"><?php echo htmlspecialchars($success); ?></div>
    <?php endif; ?>

    <?php if (empty($orders)): ?>
        <p class="text-center">Nėra užsakymų ar rezervacijų, laukiančių peržiūros.</p>
    <?php else: ?>
        <!-- Existing orders table -->
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Užsakymo ID</th>
                    <th>Vartotojo vardas</th>
                    <th>Užsakymo Data</th>
                    <th>Gamintojas</th>
                    <th>Modelis</th>
                    <th>Kiekis</th>
                    <th>Užsakymo Suma</th>
                    <th>Vartotojo Balansas</th>
                    <th>Prekės Likutis</th>
                    <th>Statusas</th>
                    <th>Rezervacijos Galiojimas</th>
                    <th>Veiksmas</th>
                </tr>
            </thead>
            <tbody>
                <?php foreach ($orders as $order): ?>
                    <?php
                    // Determine the stock status
                    $rowClass = '';
                    if ($order['stock_left'] < $order['prekiu_kiekis']) {
                        $rowClass = 'stock-insufficient';
                    } elseif ($order['stock_left'] < 10) {
                        $rowClass = 'stock-low';
                    }
                    ?>
                    <tr class="<?php echo htmlspecialchars($rowClass); ?>">
                        <td><?php echo htmlspecialchars($order['order_id']); ?></td>
                        <td>
                            <a href="review_orders.php?user_id=<?php echo htmlspecialchars($order['user_id']); ?>">
                                <?php echo htmlspecialchars($order['username']); ?>
                            </a>
                        </td>
                        <td><?php echo htmlspecialchars($order['data']); ?></td>
                        <td><?php echo htmlspecialchars($order['gamintojas']); ?></td>
                        <td><?php echo htmlspecialchars($order['modelis']); ?></td>
                        <td><?php echo htmlspecialchars($order['prekiu_kiekis']); ?></td>
                        <td><?php echo number_format($order['suma'], 2); ?> EUR</td>
                        <td><?php echo number_format($order['pinigai'], 2); ?> EUR</td>
                        <td><?php echo htmlspecialchars($order['stock_left']); ?></td>
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
                        <td>
                            <form method="POST" action="review_orders.php" style="display:inline;">
                                <input type="hidden" name="order_id" value="<?php echo htmlspecialchars($order['order_id']); ?>">
                                <?php if ($order['busena'] === 'pateiktas' || $order['busena'] === 'rezervuotas'): ?>
                                    <?php
                                    // Disable approve button if not enough stock
                                    $disableApprove = ($order['stock_left'] < $order['prekiu_kiekis']) ? 'disabled' : '';
                                    ?>
                                    <button type="submit" name="action" value="approve" class="btn btn-success btn-sm" <?php echo ($order['pinigai'] < $order['suma'] || $disableApprove) ? 'disabled' : ''; ?>>
                                        Patvirtinti
                                    </button>
                                    <button type="submit" name="action" value="reject" class="btn btn-danger btn-sm">Atmesti</button>
                                <?php else: ?>
                                    -
                                <?php endif; ?>
                            </form>
                        </td>
                    </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>

    <?php if (!empty($userOrders)): ?>
        <!-- User Order History -->
        <h3 class="mt-5">Vartotojo Užsakymų Istorija</h3>

        <!-- Filter Sidebar -->
        <div class="filter-sidebar">
            <h4>Filtras</h4>
            <form method="GET" action="review_orders.php">
                <input type="hidden" name="user_id" value="<?php echo htmlspecialchars($selectedUserId); ?>">
                <div class="form-group">
                    <label for="min_price">Min Suma</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" min="0" step="0.01" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="max_price">Max Suma</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" min="0" step="0.01" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Statusas</label><br>
                    <?php foreach ($statuses as $status): ?>
                        <?php $checked = isset($_GET['busena']) && in_array($status, $_GET['busena']) ? 'checked' : ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="busena[]" value="<?php echo htmlspecialchars($status); ?>" id="status_<?php echo htmlspecialchars($status); ?>" <?php echo $checked; ?>>
                            <label class="form-check-label" for="status_<?php echo htmlspecialchars($status); ?>">
                                <?php echo ucfirst($status); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label>Gamintojas</label><br>
                    <?php foreach ($manufacturers as $manufacturer): ?>
                        <?php $checked = isset($_GET['gamintojas']) && in_array($manufacturer, $_GET['gamintojas']) ? 'checked' : ''; ?>
                        <div class="form-check">
                            <input class="form-check-input" type="checkbox" name="gamintojas[]" value="<?php echo htmlspecialchars($manufacturer); ?>" id="manufacturer_<?php echo htmlspecialchars($manufacturer); ?>" <?php echo $checked; ?>>
                            <label class="form-check-label" for="manufacturer_<?php echo htmlspecialchars($manufacturer); ?>">
                                <?php echo htmlspecialchars($manufacturer); ?>
                            </label>
                        </div>
                    <?php endforeach; ?>
                </div>
                <div class="form-group">
                    <label for="modelis">Modelis</label>
                    <input type="text" class="form-control" id="modelis" name="modelis" placeholder="Modelis" value="<?php echo isset($_GET['modelis']) ? htmlspecialchars($_GET['modelis']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Taikyti Filtrus</button>
                <!-- Reset filters link -->
                <a href="review_orders.php?user_id=<?php echo htmlspecialchars($selectedUserId); ?>" class="btn btn-secondary btn-block">Išvalyti Filtrus</a>
            </form>
        </div>

        <!-- User Orders Table -->
        <table class="table table-bordered table-striped">
            <thead class="thead-dark">
                <tr>
                    <th>Užsakymo ID</th>
                    <th>Data</th>
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
                <?php foreach ($userOrders as $order): ?>
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

    <?php elseif (isset($selectedUserId)): ?>
        <p class="text-center">Šis vartotojas neturi užsakymų.</p>
    <?php endif; ?>
</div>

<!-- Footer -->
<?php include 'footer.php'; ?>

<!-- Scripts -->
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
