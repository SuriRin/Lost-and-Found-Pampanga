<?php
// !! View 

// ===================
// LOAD CONNECTION
// ===================
require 'config.php';

// ===================
// GET SEARCH INPUT
// ===================
$search = trim($_GET['q'] ?? '');

// ===================
// QUERY (READ + JOIN)
// ===================
$sql = "SELECT i.item_id, i.item_name, i.report_type, i.date_reported, i.status,
               c.category_name, l.place_name, l.municipality
        FROM Item i
        JOIN Category c ON i.category_id = c.category_id
        JOIN Location l ON i.location_id = l.location_id
        WHERE i.item_name LIKE ? OR l.municipality LIKE ?
        ORDER BY i.item_id DESC"; // organize table by ID

// ===================
// RUN THE QUERY
// ===================
$stmt = $pdo->prepare($sql);
$stmt->execute(["%$search%", "%$search%"]);
$items = $stmt->fetchAll();
?>

<!-- =================== -->
<!-- HTML CODE (WEBSITE) -->
<!-- =================== -->
<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Lost and Found Pampanga</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; }
    table { border-collapse: collapse; width: 100%; }
    th, td { border: 1px solid #ccc; padding: 8px; text-align: left; }
    th { background: #f0f0f0; }
  </style>
</head>
<body>
  <h1>Lost and Found Pampanga</h1>

  <!-- =================== -->
  <!-- LINK TO CREATE PAGE -->
  <!-- =================== -->
  <p><a href="item_add.php">+ Report an item</a></p>

  <!-- =================== -->
  <!-- SEARCH FORM -->
  <!-- =================== -->
  <form method="GET">
    <input name="q" placeholder="Search item or municipality"
           value="<?= htmlspecialchars($search) ?>">
    <button>Search</button>
  </form>
  <br>

  <!-- =================== -->
  <!-- RESULTS TABLE -->
  <!-- =================== -->
  <table>
    <tr>
      <th>ID</th><th>Item</th><th>Type</th><th>Category</th>
      <th>Location</th><th>Date</th><th>Status</th>
    </tr>

    <?php foreach ($items as $row): ?>
    <tr>
      <td><?= $row['item_id'] ?></td>
      <td><?= htmlspecialchars($row['item_name']) ?></td>
      <td><?= $row['report_type'] ?></td>
      <td><?= htmlspecialchars($row['category_name']) ?></td>
      <td><?= htmlspecialchars($row['place_name']) ?>, <?= htmlspecialchars($row['municipality']) ?></td>
      <td><?= $row['date_reported'] ?></td>
      <td><?= $row['status'] ?></td>
    </tr>
    <?php endforeach; ?>

    <?php if (!$items): ?>
    <tr><td colspan="7">No items found.</td></tr>
    <?php endif; ?>
  </table>
</body>
</html>