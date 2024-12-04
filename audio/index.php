<?php
session_start();
require 'db_connection.php';
date_default_timezone_set('Europe/Vilnius');
// Pagination variables
$limit = 20;  // Number of items per page
$page = isset($_GET['page']) && is_numeric($_GET['page']) ? (int) $_GET['page'] : 1;
$offset = ($page - 1) * $limit;

// Fetch unique manufacturers for the dropdown, sorted alphabetically
$manufacturersStmt = $pdo->query("SELECT DISTINCT gamintojas FROM prekes ORDER BY gamintojas ASC");
$manufacturers = $manufacturersStmt->fetchAll(PDO::FETCH_COLUMN);

// Default queries
$query = "SELECT * FROM prekes WHERE 1=1";
$countQuery = "SELECT COUNT(*) FROM prekes WHERE 1=1";
$conditions = [];
$params = [];

// Apply filters based on user input
if (!empty($_GET['min_price'])) {
    $conditions[] = "kaina >= :min_price";
    $params[':min_price'] = $_GET['min_price'];
}
if (!empty($_GET['max_price'])) {
    $conditions[] = "kaina <= :max_price";
    $params[':max_price'] = $_GET['max_price'];
}
if (!empty($_GET['paskirtis'])) {
    $placeholders = [];
    foreach ($_GET['paskirtis'] as $index => $paskirtis) {
        $paramName = ":paskirtis_$index";
        $placeholders[] = $paramName;
        $params[$paramName] = $paskirtis;
    }
    $conditions[] = "paskirtis IN (" . implode(',', $placeholders) . ")";
}
if (!empty($_GET['tipas'])) {
    $placeholders = [];
    foreach ($_GET['tipas'] as $index => $tipas) {
        $paramName = ":tipas_$index";
        $placeholders[] = $paramName;
        $params[$paramName] = $tipas;
    }
    $conditions[] = "tipas IN (" . implode(',', $placeholders) . ")";
}
if (!empty($_GET['gamintojas'])) {
    $placeholders = [];
    foreach ($_GET['gamintojas'] as $index => $gamintojas) {
        $paramName = ":gamintojas_$index";
        $placeholders[] = $paramName;
        $params[$paramName] = $gamintojas;
    }
    $conditions[] = "gamintojas IN (" . implode(',', $placeholders) . ")";
}
if (!empty($_GET['modelis'])) {
    $conditions[] = "modelis LIKE :modelis";
    $params[':modelis'] = '%' . $_GET['modelis'] . '%';
}

if (!empty($conditions)) {
    $conditionSql = implode(' AND ', $conditions);
    $query .= ' AND ' . $conditionSql;
    $countQuery .= ' AND ' . $conditionSql;
}

// Limit and offset for pagination
$query .= " LIMIT :limit OFFSET :offset";
$params[':limit'] = $limit;
$params[':offset'] = $offset;

// Prepare and execute the query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Prepare and execute the count query
$countParams = $params;
unset($countParams[':limit'], $countParams[':offset']);
$countStmt = $pdo->prepare($countQuery);
$countStmt->execute($countParams);
$totalItems = $countStmt->fetchColumn();
$totalPages = ceil($totalItems / $limit);

// Check user role
$userRole = $_SESSION['role'] ?? 'Guest';
$showSensitiveColumns = ($userRole === 'Vadybininkas' || $userRole === 'Administratorius');

// Fetch the user's balance from the database
$userId = $_SESSION['user_id'] ?? null;
if ($userId) {
    $stmt = $pdo->prepare("SELECT pinigai FROM vartotojai WHERE id = ?");
    $stmt->execute([$userId]);
    $userBalance = $stmt->fetchColumn();
} else {
    $userBalance = 0; // Default balance if not logged in
}

if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['compare'])) {
    $selectedItems = $_POST['selectedItems'] ?? [];

    if (count($selectedItems) < 2) {
        $errorMessage = "Pasirinkite bent 2 vienodo tipo prekes palyginimui.";
    } else {
        // Fetch details for selected items
        $placeholders = implode(',', array_fill(0, count($selectedItems), '?'));
        $stmt = $pdo->prepare("SELECT * FROM prekes WHERE id IN ($placeholders)");
        $stmt->execute($selectedItems);
        $selectedItemsDetails = $stmt->fetchAll();

        // Reset array keys
        $selectedItemsDetails = array_values($selectedItemsDetails);

        // Validate all selected items are of the same type
        $types = array_unique(array_column($selectedItemsDetails, 'tipas'));
        if (count($types) > 1) {
            $errorMessage = "Visos pasirinktos prekės turi būti vienodo tipo.";
        } else {
            // Determine cheapest and most expensive items
            $prices = array_column($selectedItemsDetails, 'kaina');
            $minPrice = min($prices);
            $maxPrice = max($prices);

            $cheapestIndices = [];
            $mostExpensiveIndices = [];

            foreach ($selectedItemsDetails as $index => $item) {
                if ($item['kaina'] == $minPrice) {
                    $cheapestIndices[] = $index;
                }
                if ($item['kaina'] == $maxPrice) {
                    $mostExpensiveIndices[] = $index;
                }
            }

            // Map column indices to classes
            $columnClasses = [];
            foreach ($selectedItemsDetails as $index => $item) {
                $columnClass = '';
                if (in_array($index, $cheapestIndices)) {
                    $columnClass = 'cheapest-column';
                } elseif (in_array($index, $mostExpensiveIndices)) {
                    $columnClass = 'most-expensive-column';
                }
                $columnClasses[$index] = $columnClass;
            }

            // Generate comparison results
            $comparisonResults = "<table class='table table-bordered'><thead><tr>";
            foreach ($selectedItemsDetails as $index => $item) {
                $columnClass = $columnClasses[$index];
                $comparisonResults .= "<th class='$columnClass'>{$item['gamintojas']} {$item['modelis']}</th>";
            }
            $comparisonResults .= "</tr></thead><tbody>";

            // Attributes to compare
            $attributes = [
                'paskirtis' => 'Paskirtis',
                'kaina' => 'Kaina',
                // Add more attributes here if needed
            ];

            foreach ($attributes as $attrKey => $attrLabel) {
                $comparisonResults .= "<tr>";
                foreach ($selectedItemsDetails as $index => $item) {
                    $columnClass = $columnClasses[$index];
                    if ($attrKey == 'kaina') {
                        $cellContent = number_format($item[$attrKey], 2) . " EUR";
                    } else {
                        $cellContent = htmlspecialchars($item[$attrKey]);
                    }
                    $comparisonResults .= "<td class='$columnClass'>$cellContent</td>";
                }
                $comparisonResults .= "</tr>";
            }

            $comparisonResults .= "</tbody></table>";

            // Add legend
            $comparisonResults .= '<div class="mt-3">';
            $comparisonResults .= '<span class="legend cheapest-legend"></span> Pigiausia &nbsp;';
            $comparisonResults .= '<span class="legend most-expensive-legend"></span> Brangiausia';
            $comparisonResults .= '</div>';
        }
    }
}

?>

<!DOCTYPE html>
<html lang="lt">
<head>
    <!-- Meta tags and CSS links -->
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Audio Pasaulis</title>
    <link href="https://maxcdn.bootstrapcdn.com/bootstrap/4.5.2/css/bootstrap.min.css" rel="stylesheet">
    <style>
        .hero {
            background: linear-gradient(rgba(0, 0, 0, 0.4), rgba(0, 0, 0, 0.4)), url('https://source.unsplash.com/1600x900/?audio') no-repeat center center;
            background-size: cover;
            color: #fff;
            padding: 100px 0;
            text-align: center;
        }

        .navbar-brand, .navbar-text, .btn {
            margin: 0 10px;
        }

        .filter-sidebar {
            background-color: #f8f9fa;
            border-radius: 8px;
            padding: 20px;
        }

        .filter-sidebar h4 {
            font-weight: 600;
        }

        .table-container {
            background-color: #ffffff;
            padding: 20px;
            border-radius: 8px;
            box-shadow: 0px 0px 10px rgba(0, 0, 0, 0.1);
        }

        .cheapest-column {
            background-color: #28a745 !important; /* Bootstrap success color */
            color: white;
        }

        .most-expensive-column {
            background-color: #dc3545 !important; /* Bootstrap danger color */
            color: white;
        }

        .legend {
            display: inline-block;
            width: 20px;
            height: 20px;
            margin-right: 5px;
            vertical-align: middle;
        }

        .cheapest-legend {
            background-color: #28a745;
        }

        .most-expensive-legend {
            background-color: #dc3545;
        }
    </style>
</head>
<body>

<!-- Navbar -->
<?php include 'navbar.php'; ?>

<!-- Hero Section -->
<div class="hero">
    <div class="container">
        <h1 class="display-4">Atraskite Geriausią Audio Aparatūrą</h1>
        <p class="lead">Naršykite mūsų plačią audio aparatūros kolekciją, skirtą kiekvieno poreikiams.</p>
        <a href="#filters" class="btn btn-primary btn-lg mt-3">Apsipirkti Dabar</a>
    </div>
</div>

<!-- Main Content -->
<div class="container mt-5">
    <div class="row">
        <!-- Filter Sidebar -->
        <div class="col-md-3 filter-sidebar" id="filters">
            <h4>Filtras</h4>
            <form action="index.php" method="GET">
                <div class="form-group">
                    <label for="min_price">Min Kaina</label>
                    <input type="number" class="form-control" id="min_price" name="min_price" min="0" step="0.01" value="<?php echo isset($_GET['min_price']) ? htmlspecialchars($_GET['min_price']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label for="max_price">Max Kaina</label>
                    <input type="number" class="form-control" id="max_price" name="max_price" min="0" step="0.01" value="<?php echo isset($_GET['max_price']) ? htmlspecialchars($_GET['max_price']) : ''; ?>">
                </div>
                <div class="form-group">
                    <label>Paskirtis</label><br>
                    <?php
                    $purposes = ['Auto', 'Namu kinui', 'Stereo', 'Kompiuteriams', 'Telefonams', 'Irasai', 'Kabeliai'];
                    foreach ($purposes as $purpose) {
                        $checked = isset($_GET['paskirtis']) && in_array($purpose, $_GET['paskirtis']) ? 'checked' : '';
                        echo "<div class='form-check'>
                                <input class='form-check-input' type='checkbox' name='paskirtis[]' value='$purpose' id='$purpose' $checked>
                                <label class='form-check-label' for='$purpose'>$purpose</label>
                              </div>";
                    }
                    ?>
                </div>
                <div class="form-group">
                    <label>
                        <a href="#collapseType" data-toggle="collapse" aria-expanded="false" aria-controls="collapseType">
                            Tipas <span class="caret"></span>
                        </a>
                    </label>
                    <div class="collapse" id="collapseType">
                        <?php
                        $types = ['CD', 'Vinilines ploksteles', 'Garsiakalbiai', 'Ausines', 'Stiprintuvai', 'Garso procesoriai'];
                        foreach ($types as $type) {
                            $checked = isset($_GET['tipas']) && in_array($type, $_GET['tipas']) ? 'checked' : '';
                            echo "<div class='form-check'>
                                    <input class='form-check-input' type='checkbox' name='tipas[]' value='$type' id='$type' $checked>
                                    <label class='form-check-label' for='$type'>$type</label>
                                  </div>";
                        }
                        ?>
                    </div>
                </div>
                <div class="form-group">
                    <label>
                        <a href="#collapseManufacturer" data-toggle="collapse" aria-expanded="false" aria-controls="collapseManufacturer">
                            Gamintojas <span class="caret"></span>
                        </a>
                    </label>
                    <div class="collapse" id="collapseManufacturer">
                        <?php foreach ($manufacturers as $manufacturer): ?>
                            <?php $checked = isset($_GET['gamintojas']) && in_array($manufacturer, $_GET['gamintojas']) ? 'checked' : ''; ?>
                            <div class="form-check">
                                <input class="form-check-input" type="checkbox" name="gamintojas[]" value="<?php echo htmlspecialchars($manufacturer); ?>" id="<?php echo htmlspecialchars($manufacturer); ?>" <?php echo $checked; ?>>
                                <label class="form-check-label" for="<?php echo htmlspecialchars($manufacturer); ?>">
                                    <?php echo htmlspecialchars($manufacturer); ?>
                                </label>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
                <div class="form-group">
                    <label for="modelis">Modelis</label>
                    <input type="text" class="form-control" id="modelis" name="modelis" placeholder="Modelis" value="<?php echo isset($_GET['modelis']) ? htmlspecialchars($_GET['modelis']) : ''; ?>">
                </div>
                <button type="submit" class="btn btn-primary btn-block">Taikyti Filtrus</button>
                <a href="index.php" class="btn btn-secondary btn-block">Išvalyti Filtrus</a>
            </form>
        </div>

        <!-- Equipment Table and Other Content -->
        <div class="col-md-9">
            <!-- Display success or error messages -->
            <?php if (isset($_GET['cart_success'])): ?>
                <div class="alert alert-success">Prekė pridėta į krepšelį.</div>
            <?php elseif (isset($_GET['cart_error'])): ?>
                <div class="alert alert-danger">Klaida pridedant prekę į krepšelį.</div>
            <?php endif; ?>

            <form id="compareForm" method="POST" action="index.php">
                <div class="table-container">
                    <h2 class="text-center mb-4">Audio Aparatūra</h2>
                    <table class="table table-bordered table-striped">
                        <thead class="thead-dark">
                            <tr>
                                <th>Pasirinkti</th>
                                <?php if ($showSensitiveColumns): ?>
                                    <th>ID</th>
                                <?php endif; ?>
                                <th>Gamintojas</th>
                                <th>Modelis</th>
                                <th>Paskirtis</th>
                                <th>Tipas</th>
                                <th>Kaina</th>
                                <?php if ($showSensitiveColumns): ?>
                                    <th>Likutis</th>
                                <?php endif; ?>
                                <?php if ($userRole === 'Vartotojas'): ?>
                                    <th>Kiekis</th>
                                    <th>Veiksmas</th>
                                <?php endif; ?>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (count($items) > 0): ?>
                                <?php foreach ($items as $item): ?>
                                    <tr>
                                        <td>
                                            <input type="checkbox" name="selectedItems[]" value="<?php echo htmlspecialchars($item['id']); ?>" data-type="<?php echo htmlspecialchars($item['tipas']); ?>">
                                        </td>
                                        <?php if ($showSensitiveColumns): ?>
                                            <td><?php echo htmlspecialchars($item['id']); ?></td>
                                        <?php endif; ?>
                                        <td><?php echo htmlspecialchars($item['gamintojas']); ?></td>
                                        <td><?php echo htmlspecialchars($item['modelis']); ?></td>
                                        <td><?php echo htmlspecialchars($item['paskirtis']); ?></td>
                                        <td><?php echo htmlspecialchars($item['tipas']); ?></td>
                                        <td><?php echo number_format($item['kaina'], 2); ?> EUR</td>
                                        <?php if ($showSensitiveColumns): ?>
                                            <td><?php echo htmlspecialchars($item['likutis']); ?></td>
                                        <?php endif; ?>
                                        <?php if ($userRole === 'Vartotojas'): ?>
                                            <td>
                                                <input type="number" name="quantity" min="1" value="1" class="form-control" style="width: 80px;">
                                            </td>
                                            <td>
                                                <form method="POST" action="add_to_cart.php">
                                                    <input type="hidden" name="item_id" value="<?php echo htmlspecialchars($item['id']); ?>">
                                                    <input type="hidden" name="quantity" value="1">
                                                    <button type="submit" class="btn btn-success btn-sm">Į krepšelį</button>
                                                </form>
                                            </td>
                                        <?php endif; ?>
                                    </tr>
                                <?php endforeach; ?>
                            <?php else: ?>
                                <tr>
                                    <td colspan="<?php echo $showSensitiveColumns ? '8' : '7'; ?>" class="text-center">Nėra rezultatų</td>
                                </tr>
                            <?php endif; ?>
                        </tbody>
                    </table>
                    <button type="submit" name="compare" class="btn btn-primary">Palyginti</button>
                </div>
            </form>

            <!-- Pagination Links -->
            <nav aria-label="Page navigation">
                <ul class="pagination justify-content-center mt-4">
                    <?php for ($i = 1; $i <= $totalPages; $i++): ?>
                        <li class="page-item <?php if ($i == $page) echo 'active'; ?>">
                            <a class="page-link" href="?<?php
                                $queryParams = $_GET;
                                $queryParams['page'] = $i;
                                echo http_build_query($queryParams);
                            ?>"><?php echo $i; ?></a>
                        </li>
                    <?php endfor; ?>
                </ul>
            </nav>

            <!-- Comparison Results -->
            <?php if (isset($comparisonResults)): ?>
                <div class="mt-4">
                    <h3>Palyginimo Rezultatai:</h3>
                    <?php echo $comparisonResults; ?>
                </div>
            <?php elseif (isset($errorMessage)): ?>
                <div class="alert alert-danger mt-4"><?php echo $errorMessage; ?></div>
            <?php endif; ?>
        </div>
    </div>
</div>
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
