<?php
// !! Create

// ===================
// LOAD CONNECTION
// ===================
require 'config.php';

// ===================
// SETUP VARIABLES
// ===================
$errors = [];
$old = ['item_name'=>'','description'=>'','report_type'=>'','date_reported'=>'','category_id'=>'','location_id'=>''];

// ===================
// LOAD DROPDOWN DATA
// ===================
$categories = $pdo->query("SELECT * FROM Category ORDER BY category_name")->fetchAll();
$locations  = $pdo->query("SELECT * FROM Location ORDER BY municipality, place_name")->fetchAll();

// ===================
// HANDLE FORM SUBMIT
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }

    // ===================
    // VALIDATION
    // ===================
    if ($old['item_name'] === '') $errors[] = "Item name is required.";
    elseif (strlen($old['item_name']) > 100) $errors[] = "Item name must be 100 characters or less.";

    if (!in_array($old['report_type'], ['Lost','Found'], true)) $errors[] = "Choose Lost or Found.";

    $d = DateTime::createFromFormat('Y-m-d', $old['date_reported']);
    if (!$d || $d->format('Y-m-d') !== $old['date_reported']) {
        $errors[] = "Enter a valid date.";
    } elseif ($old['date_reported'] > date('Y-m-d')) {
        $errors[] = "Date reported cannot be in the future.";
    }

    $chk = $pdo->prepare("SELECT COUNT(*) FROM Category WHERE category_id = ?");
    $chk->execute([$old['category_id']]);
    if (!$chk->fetchColumn()) $errors[] = "Choose a valid category.";

    $chk = $pdo->prepare("SELECT COUNT(*) FROM Location WHERE location_id = ?");
    $chk->execute([$old['location_id']]);
    if (!$chk->fetchColumn()) $errors[] = "Choose a valid location.";

    // ===================
    // INSERT (CREATE)
    // ===================
    if (!$errors) {
        $stmt = $pdo->prepare(
            "INSERT INTO Item (item_name, description, report_type, date_reported, category_id, location_id)
             VALUES (?, ?, ?, ?, ?, ?)"
        );
        $stmt->execute([
            $old['item_name'], $old['description'], $old['report_type'],
            $old['date_reported'], $old['category_id'], $old['location_id']
        ]);

        header("Location: items.php?added=1");
        exit;
    }
}
?>

<!-- =================== -->
<!-- HTML CODE (WEBSITE) -->
<!-- =================== -->

<!DOCTYPE html>
<html>
<head>
  <meta charset="UTF-8">
  <title>Add Item</title>
  <style>
    body { font-family: Arial, sans-serif; margin: 30px; max-width: 500px; }
    label { display: block; margin-top: 12px; }
    input, select, textarea { width: 100%; padding: 6px; }
    .error { background: #fdd; border: 1px solid #c00; padding: 8px; margin-bottom: 10px; }
  </style>
</head>
<body>
  <h1>Report an Item</h1>
  <p><a href="items.php">&larr; Back to list</a></p>

  <!-- =================== -->
  <!-- ERROR MESSAGES -->
  <!-- =================== -->
  <?php if ($errors): ?>
    <div class="error">
      <?php foreach ($errors as $e): ?>
        <?= htmlspecialchars($e) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <!-- =================== -->
  <!-- THE FORM -->
  <!-- =================== -->
  <form method="POST">
    <label>Item name
      <input name="item_name" value="<?= htmlspecialchars($old['item_name']) ?>">
    </label>

    <label>Description
      <textarea name="description" rows="3"><?= htmlspecialchars($old['description']) ?></textarea>
    </label>

    <label>Type
      <select name="report_type">
        <option value="">-- choose --</option>
        <option value="Lost"  <?= $old['report_type']==='Lost'  ? 'selected' : '' ?>>Lost</option>
        <option value="Found" <?= $old['report_type']==='Found' ? 'selected' : '' ?>>Found</option>
      </select>
    </label>

    <label>Date reported
      <input type="date" name="date_reported" value="<?= htmlspecialchars($old['date_reported']) ?>">
    </label>

    <label>Category
      <select name="category_id">
        <option value="">-- choose --</option>
        <?php foreach ($categories as $c): ?>
          <option value="<?= $c['category_id'] ?>" <?= $old['category_id']==$c['category_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($c['category_name']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Location
      <select name="location_id">
        <option value="">-- choose --</option>
        <?php foreach ($locations as $l): ?>
          <option value="<?= $l['location_id'] ?>" <?= $old['location_id']==$l['location_id'] ? 'selected' : '' ?>>
            <?= htmlspecialchars($l['place_name']) ?>, <?= htmlspecialchars($l['municipality']) ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <br><br>
    <button>Save item</button>
  </form>
</body>
</html>