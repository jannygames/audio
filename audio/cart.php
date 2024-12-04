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

$userId = $_SESSION['user_id'];

// Fetch cart items from the database
$stmt = $pdo->prepare("SELECT item_id, quantity FROM krepselis WHERE user_id = ?");
$stmt->execute([$userId]);
$cartItems = $stmt->fetchAll(PDO::FETCH_KEY_PAIR); // Returns an array with item_id as key and quantity as value

// Fetch item details from the database if there are items in the cart
$itemDetails = [];
$totalCost = 0;

if (!empty($cartItems)) {
    $placeholders = implode(',', array_fill(0, count($cartItems), '?'));
    $stmt = $pdo->prepare("SELECT * FROM prekes WHERE id IN ($placeholders)");
    $stmt->execute(array_keys($cartItems));
    $fetchedItems = $stmt->fetchAll(PDO::FETCH_ASSOC);

    // Calculate total price and item details
    foreach ($fetchedItems as $item) {
        $itemId = $item['id'];
        $quantity = $cartItems[$itemId];

        $itemDetails[] = [
            'id' => $itemId,
            'gamintojas' => $item['gamintojas'],
            'modelis' => $item['modelis'],
            'kaina' => $item['kaina'],
            'quantity' => $quantity,
            'total_price' => $item['kaina'] * $quantity,
        ];

        $totalCost += $item['kaina'] * $quantity;
    }
}

// Handle quantity updates, item removals, and purchases/reservations
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_quantity'])) {
        // Update quantities
        foreach ($_POST['quantity'] as $itemId => $quantity) {
            if ($quantity > 0) {
                // Update the quantity in the database
                $stmt = $pdo->prepare("UPDATE krepselis SET quantity = ? WHERE user_id = ? AND item_id = ?");
                $stmt->execute([$quantity, $userId, $itemId]);
            } else {
                // Remove item from cart
                $stmt = $pdo->prepare("DELETE FROM krepselis WHERE user_id = ? AND item_id = ?");
                $stmt->execute([$userId, $itemId]);
            }
        }
        header("Location: cart.php");
        exit();
    } elseif (isset($_POST['remove_item'])) {
        // Remove specific item
        $itemIdToRemove = $_POST['remove_item'];
        $stmt = $pdo->prepare("DELETE FROM krepselis WHERE user_id = ? AND item_id = ?");
        $stmt->execute([$userId, $itemIdToRemove]);
        header("Location: cart.php");
        exit();
    } elseif (isset($_POST['reserve'])) {
        // Reserve items for a week
        $pdo->beginTransaction();
        try {
            foreach ($itemDetails as $item) {
                // Insert reservation into 'uzsakymai' table with status 'rezervuotas' (reserved)
                $stmt = $pdo->prepare("INSERT INTO uzsakymai (data, uzsakyta_preke, prekiu_kiekis, suma, busena, user_id, rezervacijos_galiojimo_data)
                    VALUES (NOW(), ?, ?, ?, 'rezervuotas', ?, DATE_ADD(NOW(), INTERVAL 7 DAY))");
                $stmt->execute([$item['id'], $item['quantity'], $item['total_price'], $userId]);

                // Deduct stock from `prekes` table to reflect reservation
                $stmt = $pdo->prepare("UPDATE prekes SET likutis = likutis - ? WHERE id = ?");
                $stmt->execute([$item['quantity'], $item['id']]);
            }

            // Clear cart in database
            $stmt = $pdo->prepare("DELETE FROM krepselis WHERE user_id = ?");
            $stmt->execute([$userId]);

            $pdo->commit();
            header("Location: cart.php?reservation_success=1");
            exit();
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = "Rezervacija nepavyko: " . $e->getMessage();
        }
    } elseif (isset($_POST['buy'])) {
        // Proceed to purchase if the user has enough balance
        // Fetch the user's balance from the database
        $stmt = $pdo->prepare("SELECT pinigai FROM vartotojai WHERE id = ?");
        $stmt->execute([$userId]);
        $userBalance = $stmt->fetchColumn();

        if ($userBalance >= $totalCost) {
            $pdo->beginTransaction();
            try {
                foreach ($itemDetails as $item) {
                    // Insert order into 'uzsakymai' table with status 'pateiktas' (submitted)
                    $stmt = $pdo->prepare("INSERT INTO uzsakymai (data, uzsakyta_preke, prekiu_kiekis, suma, busena, user_id)
                        VALUES (NOW(), ?, ?, ?, 'pateiktas', ?)");
                    $stmt->execute([$item['id'], $item['quantity'], $item['total_price'], $userId]);

                    // Deduct stock from `prekes` table
                    $stmt = $pdo->prepare("UPDATE prekes SET likutis = likutis - ? WHERE id = ?");
                    $stmt->execute([$item['quantity'], $item['id']]);
                }

                // Deduct user's balance
                $stmt = $pdo->prepare("UPDATE vartotojai SET pinigai = pinigai - ? WHERE id = ?");
                $stmt->execute([$totalCost, $userId]);

                // Clear cart in database
                $stmt = $pdo->prepare("DELETE FROM krepselis WHERE user_id = ?");
                $stmt->execute([$userId]);

                $pdo->commit();
                header("Location: cart.php?purchase_success=1");
                exit();
            } catch (Exception $e) {
                $pdo->rollBack();
                $error = "Pirkimas nepavyko: " . $e->getMessage();
            }
        } else {
            $error = "Nepakanka lėšų pirkimui. Galite rezervuoti prekes.";
        }
    }
}

// Fetch the user's balance from the database if not already fetched
if (!isset($userBalance)) {
    $stmt = $pdo->prepare("SELECT pinigai FROM vartotojai WHERE id = ?");
    $stmt->execute([$userId]);
    $userBalance = $stmt->fetchColumn();
}
?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <meta charset="UTF-8">
    <title>Krepšelis</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        /* Styling */
        body {
            background-color: #f4f6f9;
            font-family: Arial, sans-serif;
            display: flex;
            flex-direction: column;
            min-height: 100vh;
        }
        .content {
            flex: 1 0 auto;
        }
        header, footer {
            flex: 0 0 auto;
        }
        .container-a {
            background-color: #ffffff;
            border-radius: 8px;
            box-shadow: 0px 4px 12px rgba(0, 0, 0, 0.1);
            padding: 20px;
        }
        .table {
            border-radius: 5px;
            overflow: hidden;
        }
        .table thead {
            background-color: #343a40;
            color: #fff;
        }
        .total-cost {
            font-weight: bold;
            color: #28a745;
            font-size: 20px;
        }
        .alert {
            font-size: 16px;
            border-radius: 5px;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include 'navbar.php'; ?>

<!-- Content -->
<div class="container-a mt-5 content">
    <h2 class="text-center mb-4">Jūsų Krepšelis</h2>

    <?php if (isset($error)): ?>
        <div class="alert alert-danger"><?php echo htmlspecialchars($error); ?></div>
    <?php elseif (isset($_GET['purchase_success'])): ?>
        <div class="alert alert-success">Ačiū! Jūsų užsakymas sėkmingai pateiktas.</div>
    <?php elseif (isset($_GET['reservation_success'])): ?>
        <div class="alert alert-success">Prekės sėkmingai rezervuotos vienai savaitei.</div>
    <?php endif; ?>

    <?php if (empty($itemDetails)): ?>
        <p class="text-center">Jūsų krepšelis tuščias. <a href="index.php">Tęsti apsipirkimą</a></p>
    <?php else: ?>
        <form method="POST" action="cart.php">
            <table class="table table-hover">
                <thead class="thead-dark">
                    <tr>
                        <th>Gamintojas</th>
                        <th>Modelis</th>
                        <th>Kaina</th>
                        <th>Kiekis</th>
                        <th>Bendra Kaina</th>
                        <th>Veiksmas</th>
                    </tr>
                </thead>
                <tbody>
                    <?php foreach ($itemDetails as $item): ?>
                        <tr>
                            <td><?php echo htmlspecialchars($item['gamintojas']); ?></td>
                            <td><?php echo htmlspecialchars($item['modelis']); ?></td>
                            <td><?php echo number_format($item['kaina'], 2); ?> EUR</td>
                            <td>
                                <input type="number" name="quantity[<?php echo $item['id']; ?>]" value="<?php echo $item['quantity']; ?>" min="1" class="form-control" style="width: 80px;">
                            </td>
                            <td><?php echo number_format($item['total_price'], 2); ?> EUR</td>
                            <td>
                                <button type="submit" name="remove_item" value="<?php echo $item['id']; ?>" class="btn btn-danger btn-sm">Pašalinti</button>
                            </td>
                        </tr>
                    <?php endforeach; ?>
                </tbody>
            </table>

            <div class="text-right mt-4">
                <span class="total-cost">Suma: <?php echo number_format($totalCost, 2); ?> EUR</span>
            </div>

            <div class="d-flex justify-content-between mt-3">
                <a href="index.php" class="btn btn-secondary">Tęsti apsipirkimą</a>
                <div>
                    <button type="submit" name="update_quantity" class="btn btn-primary">Atnaujinti krepšelį</button>
                    <button type="submit" name="reserve" class="btn btn-warning">Rezervuoti</button>
                    <button type="submit" name="buy" class="btn btn-success">Pirkti</button>
                </div>
            </div>
        </form>
    <?php endif; ?>
</div>

<!-- Footer -->
<?php include 'footer.php'; ?>

<script src="https://code.jquery.com/jquery-3.5.1.min.js"></script>
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
