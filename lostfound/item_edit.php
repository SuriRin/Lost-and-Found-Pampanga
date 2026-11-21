<?php
// !! Edit Own Report
//    flowchart: My Reports -> View and Manage Own Reports (edit)
//    Business rule 3: a report belongs to the user who created it,
//    so only the owner can edit it.
//    Business rule: a user can update the status of a lost item to Found or Cancelled,
//    and can set it back to Lost if that was a mistake.
//    Locked when: a lost report is Claimed, a found item is no longer Available,
//    or a found item already has claims.

require __DIR__ . '/includes/config.php';
require_login();

$uid  = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);

// Fixed list of table and column names, never taken from the visitor
$tables = [
    'Lost'  => ['table' => 'Lost_Item',  'id' => 'lost_item_id',  'date' => 'date_lost',  'open' => 'Lost'],
    'Found' => ['table' => 'Found_Item', 'id' => 'found_item_id', 'date' => 'date_found', 'open' => 'Available'],
];

// Statuses a user may set on their own lost report. Going back to Lost reopens the
// report, which covers a false "Found". Anything else (for example Claimed) stays locked.
$lostStatuses = ['Lost', 'Found', 'Cancelled'];

// Image upload settings (same as the report forms)
$maxImageBytes = 2 * 1024 * 1024;                        // 2 MB
$allowedImages = [
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$uploadDir = __DIR__ . '/uploads';

// ===================
// FETCH THE REPORT (owner only)
// A report that belongs to someone else looks the same as one that doesn't exist.
// ===================
$row = false;
if (isset($tables[$type]) && $id > 0) {
    $t = $tables[$type];
    $stmt = $pdo->prepare("SELECT * FROM {$t['table']} WHERE {$t['id']} = ? AND user_id = ?");
    $stmt->execute([$id, $uid]);
    $row = $stmt->fetch();
}

if (!$row) {
    http_response_code(404);
    $pageTitle = 'Report not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card narrow center">
      <h1>Report not found</h1>
      <p class="muted">That report does not exist, or it is not yours.</p>
      <p><a href="<?= BASE_URL ?>/my_reports.php">&larr; Back to my reports</a></p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ===================
// CAN IT BE EDITED?
// $locked holds the reason when the answer is no.
// ===================
$locked = null;
if ($type === 'Lost') {
    // A lost report stays editable while Lost, Found or Cancelled,
    // so a wrong "Found" can be undone. Claimed (set by admin) stays locked.
    if (!in_array($row['status'], $lostStatuses, true)) {
        $locked = "This report is already marked as " . strtolower($row['status']) . ", so it can't be edited.";
    }
} elseif ($row['status'] !== $t['open']) {
    $locked = "This report is already marked as " . strtolower($row['status']) . ", so it can't be edited.";
} else {
    $chk = $pdo->prepare("SELECT COUNT(*) FROM Claim WHERE found_item_id = ?");
    $chk->execute([$id]);
    if ((int)$chk->fetchColumn() > 0) {
        $locked = "This found item already has claims, so it can't be edited.";
    }
}

// ===================
// DROPDOWN DATA
// ===================
$categories = $pdo->query("SELECT category_id, category_name FROM Category ORDER BY category_name")->fetchAll();
$locations  = $pdo->query("SELECT location_id, location_name, building FROM Location ORDER BY location_name")->fetchAll();

$validCategoryIds = array_map('intval', array_column($categories, 'category_id'));
$validLocationIds = array_map('intval', array_column($locations, 'location_id'));

$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');

// ===================
// SETUP VARIABLES (start with the saved values)
// ===================
$errors = [];
$old = [
    'item_name'   => $row['item_name'],
    'category_id' => (string)$row['category_id'],
    'location_id' => (string)$row['location_id'],
    'item_date'   => $row[$t['date']],
    'description' => $row['description'] ?? '',
];

// ===================
// HANDLE FORM SUBMIT
// ===================
if (!$locked && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }
        $removeImage = isset($_POST['remove_image']);

        // ===================
        // VALIDATION
        // ===================
        if ($old['item_name'] === '') {
            $errors[] = "Item name is required.";
        } elseif (mb_strlen($old['item_name']) > 100) {
            $errors[] = "Item name must be 100 characters or less.";
        }

        if (!in_array((int)$old['category_id'], $validCategoryIds, true)) {
            $errors[] = "Please choose a category.";
        }

        if (!in_array((int)$old['location_id'], $validLocationIds, true)) {
            $errors[] = "Please choose a location.";
        }

        $dateLabel = $type === 'Lost' ? 'date lost' : 'date found';
        $d = DateTime::createFromFormat('Y-m-d', $old['item_date']);
        if ($old['item_date'] === '') {
            $errors[] = "The $dateLabel is required.";
        } elseif (!$d || $d->format('Y-m-d') !== $old['item_date']) {
            $errors[] = "Enter a valid $dateLabel.";
        } elseif ($old['item_date'] > $today) {
            $errors[] = "The $dateLabel cannot be in the future.";
        }

        if (mb_strlen($old['description']) > 1000) {
            $errors[] = "Description must be 1000 characters or less.";
        }

        // Status: the owner can set a lost report to Lost, Found or Cancelled.
        // Anything else (for example a tampered "Claimed") keeps the current status.
        $newStatus = $row['status'];
        if ($type === 'Lost') {
            $picked = $_POST['status'] ?? $row['status'];
            if (in_array($picked, $lostStatuses, true)) {
                $newStatus = $picked;
            }
        }

        // New picture is optional. Check it before saving anything.
        $imageExt = null;
        $file = $_FILES['image'] ?? null;
        if ($file && $file['error'] !== UPLOAD_ERR_NO_FILE) {
            if ($file['error'] === UPLOAD_ERR_INI_SIZE || $file['error'] === UPLOAD_ERR_FORM_SIZE) {
                $errors[] = "The picture is too large. The limit is 2 MB.";
            } elseif ($file['error'] !== UPLOAD_ERR_OK) {
                $errors[] = "The picture could not be uploaded. Please try again.";
            } elseif ($file['size'] > $maxImageBytes) {
                $errors[] = "The picture is too large. The limit is 2 MB.";
            } else {
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                if (!isset($allowedImages[$mime]) || @getimagesize($file['tmp_name']) === false) {
                    $errors[] = "The picture must be a JPG, PNG or WEBP image.";
                } else {
                    $imageExt = $allowedImages[$mime];
                }
            }
        }

        // ===================
        // UPDATE
        // ===================
        if (!$errors) {
            $oldImage  = $row['image'];
            $imageName = $oldImage;          // keep the current picture by default
            $newSaved  = false;

            if ($imageExt !== null) {
                // Replace the picture
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $imageName = bin2hex(random_bytes(16)) . '.' . $imageExt;
                if (move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $imageName)) {
                    $newSaved = true;
                } else {
                    $errors[] = "The picture could not be saved. Please try again.";
                    $imageName = $oldImage;
                }
            } elseif ($removeImage) {
                $imageName = null;           // just remove the picture
            }

            if (!$errors) {
                try {
                    $stmt = $pdo->prepare(
                        "UPDATE {$t['table']}
                         SET category_id = ?, location_id = ?, item_name = ?,
                             description = ?, {$t['date']} = ?, image = ?, status = ?
                         WHERE {$t['id']} = ? AND user_id = ?"
                    );
                    $stmt->execute([
                        (int)$old['category_id'],
                        (int)$old['location_id'],
                        $old['item_name'],
                        $old['description'] !== '' ? $old['description'] : null,
                        $old['item_date'],
                        $imageName,
                        $newStatus,
                        $id,
                        $uid,
                    ]);

                    // The row is saved, so the old picture file is no longer needed
                    if ($oldImage && $imageName !== $oldImage) {
                        @unlink($uploadDir . '/' . basename($oldImage));
                    }
                    redirect('item_view.php?type=' . $type . '&id=' . $id);
                } catch (PDOException $ex) {
                    // Don't leave an orphan picture behind if the database refused the update
                    if ($newSaved) {
                        @unlink($uploadDir . '/' . $imageName);
                    }
                    throw $ex;
                }
            }
        }
    }
}

$pageTitle = 'Edit report';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/my_reports.php">&larr; Back to my reports</a></p>

<div class="card narrow">
  <h1>Edit <?= $type === 'Lost' ? 'lost' : 'found' ?> report</h1>

  <?php if ($locked): ?>

    <div class="alert error"><?= e($locked) ?></div>
    <p class="center"><a href="<?= BASE_URL ?>/item_view.php?type=<?= e($type) ?>&amp;id=<?= (int)$id ?>">View the report</a></p>

  <?php else: ?>

    <?php if ($errors): ?>
      <div class="alert error">
        <?php foreach ($errors as $err): ?>
          <?= e($err) ?><br>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" enctype="multipart/form-data" novalidate>
      <?= csrf_field() ?>

      <label>Item name
        <input name="item_name" maxlength="100" value="<?= e($old['item_name']) ?>">
      </label>

      <label>Category
        <select name="category_id">
          <option value="">Choose a category</option>
          <?php foreach ($categories as $c): ?>
            <option value="<?= (int)$c['category_id'] ?>" <?= (int)$old['category_id'] === (int)$c['category_id'] ? 'selected' : '' ?>>
              <?= e($c['category_name']) ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label><?= $type === 'Lost' ? 'Where did you lose it?' : 'Where did you find it?' ?>
        <select name="location_id">
          <option value="">Choose a location</option>
          <?php foreach ($locations as $l): ?>
            <option value="<?= (int)$l['location_id'] ?>" <?= (int)$old['location_id'] === (int)$l['location_id'] ? 'selected' : '' ?>>
              <?= e($l['location_name']) ?><?= $l['building'] ? ' (' . e($l['building']) . ')' : '' ?>
            </option>
          <?php endforeach; ?>
        </select>
      </label>

      <label><?= $type === 'Lost' ? 'Date lost' : 'Date found' ?>
        <input type="date" name="item_date" max="<?= e($today) ?>" value="<?= e($old['item_date']) ?>">
      </label>

      <label>Description <span class="muted">(optional)</span>
        <textarea name="description" rows="4" maxlength="1000"><?= e($old['description']) ?></textarea>
      </label>

      <?php if ($row['image']): ?>
        <label>Current picture
          <img class="detail-img" style="max-height: 180px;" src="<?= BASE_URL ?>/uploads/<?= e($row['image']) ?>" alt="Current picture">
        </label>
        <label class="check" style="display: flex; align-items: center; gap: 8px; font-weight: 400;">
          <input type="checkbox" name="remove_image" value="1" style="width: auto; margin: 0;">
          Remove this picture
        </label>
      <?php endif; ?>

      <label><?= $row['image'] ? 'Replace picture' : 'Picture' ?> <span class="muted">(optional, JPG, PNG or WEBP, up to 2 MB)</span>
        <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
      </label>

      <?php if ($type === 'Lost'): ?>
        <label>Status
          <select name="status">
            <option value="Lost"      <?= $row['status'] === 'Lost'      ? 'selected' : '' ?>>Still lost</option>
            <option value="Found"     <?= $row['status'] === 'Found'     ? 'selected' : '' ?>>I found it</option>
            <option value="Cancelled" <?= $row['status'] === 'Cancelled' ? 'selected' : '' ?>>Cancel this report</option>
          </select>
        </label>
      <?php endif; ?>

      <button class="btn">Save changes</button>
    </form>

    <p class="muted center"><a href="<?= BASE_URL ?>/my_reports.php">Cancel</a></p>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>