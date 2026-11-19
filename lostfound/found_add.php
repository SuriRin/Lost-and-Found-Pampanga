<?php
// !! Report Found Item
//    flowchart: Choose Action -> Report Found Item -> Fill Out Found Item Form
//               -> Submit Found Item Report -> Save Found Item Report -> User Dashboard
//    Business rules: 1 (must be logged in), 2 (a user can post many reports),
//                    3 (every report belongs to the user who created it)

require __DIR__ . '/includes/config.php';
require_login();

$uid = $_SESSION['user_id'];

// Image upload settings
$maxImageBytes = 2 * 1024 * 1024;                        // 2 MB
$allowedImages = [                                        // real MIME type => file extension
    'image/jpeg' => 'jpg',
    'image/png'  => 'png',
    'image/webp' => 'webp',
];
$uploadDir = __DIR__ . '/uploads';

// ===================
// DROPDOWN DATA
// ===================
$categories = $pdo->query("SELECT category_id, category_name FROM Category ORDER BY category_name")->fetchAll();
$locations  = $pdo->query("SELECT location_id, location_name, building FROM Location ORDER BY location_name")->fetchAll();

$validCategoryIds = array_map('intval', array_column($categories, 'category_id'));
$validLocationIds = array_map('intval', array_column($locations, 'location_id'));

// "Today" in the Philippines, so a report made early in the morning is not rejected as a future date
$today = (new DateTime('now', new DateTimeZone('Asia/Manila')))->format('Y-m-d');

// ===================
// SETUP VARIABLES
// ===================
$errors = [];
$old = [
    'item_name'   => '',
    'category_id' => '',
    'location_id' => '',
    'date_found'  => $today,
    'description' => '',
];

// ===================
// HANDLE FORM SUBMIT
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        foreach ($old as $k => $_) {
            $old[$k] = trim($_POST[$k] ?? '');
        }

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
            $errors[] = "Please choose where you found the item.";
        }

        // Date must be a real calendar date (YYYY-MM-DD) and not in the future
        $d = DateTime::createFromFormat('Y-m-d', $old['date_found']);
        if ($old['date_found'] === '') {
            $errors[] = "Date found is required.";
        } elseif (!$d || $d->format('Y-m-d') !== $old['date_found']) {
            $errors[] = "Enter a valid date found.";
        } elseif ($old['date_found'] > $today) {
            $errors[] = "Date found cannot be in the future.";
        }

        if (mb_strlen($old['description']) > 1000) {
            $errors[] = "Description must be 1000 characters or less.";
        }

        // Image is optional. If one was chosen, check it before saving anything.
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
                // Check the real file contents, not the name or the browser's claim
                $mime = (new finfo(FILEINFO_MIME_TYPE))->file($file['tmp_name']);
                if (!isset($allowedImages[$mime]) || @getimagesize($file['tmp_name']) === false) {
                    $errors[] = "The picture must be a JPG, PNG or WEBP image.";
                } else {
                    $imageExt = $allowedImages[$mime];
                }
            }
        }

        // ===================
        // INSERT (CREATE)
        // status defaults to 'Available' and date_posted to now
        // ===================
        if (!$errors) {
            $imageName = null;

            if ($imageExt !== null) {
                if (!is_dir($uploadDir)) {
                    mkdir($uploadDir, 0755, true);
                }
                $imageName = bin2hex(random_bytes(16)) . '.' . $imageExt;   // never use the visitor's file name
                if (!move_uploaded_file($file['tmp_name'], $uploadDir . '/' . $imageName)) {
                    $errors[] = "The picture could not be saved. Please try again.";
                    $imageName = null;
                }
            }

            if (!$errors) {
                try {
                    $stmt = $pdo->prepare(
                        "INSERT INTO Found_Item
                            (user_id, category_id, location_id, item_name, description, date_found, image)
                         VALUES (?, ?, ?, ?, ?, ?, ?)"
                    );
                    $stmt->execute([
                        $uid,
                        (int)$old['category_id'],
                        (int)$old['location_id'],
                        $old['item_name'],
                        $old['description'] !== '' ? $old['description'] : null,
                        $old['date_found'],
                        $imageName,
                    ]);
                    $newId = (int)$pdo->lastInsertId();

                    // Flowchart: Save Found Item Report -> show the saved report
                    redirect('item_view.php?type=Found&id=' . $newId);
                } catch (PDOException $ex) {
                    // Don't leave an orphan picture behind if the database refused the row
                    if ($imageName !== null) {
                        @unlink($uploadDir . '/' . $imageName);
                    }
                    throw $ex;
                }
            }
        }
    }
}

$pageTitle = 'Report found item';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/dashboard.php">&larr; Back to dashboard</a></p>

<div class="card narrow">
  <h1>Report a found item</h1>
  <p class="muted">Post something you found so its owner can send a claim. An administrator reviews every claim.</p>

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
      <input name="item_name" maxlength="100" placeholder="e.g. Black leather wallet" value="<?= e($old['item_name']) ?>">
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

    <label>Where did you find it?
      <select name="location_id">
        <option value="">Choose a location</option>
        <?php foreach ($locations as $l): ?>
          <option value="<?= (int)$l['location_id'] ?>" <?= (int)$old['location_id'] === (int)$l['location_id'] ? 'selected' : '' ?>>
            <?= e($l['location_name']) ?><?= $l['building'] ? ' (' . e($l['building']) . ')' : '' ?>
          </option>
        <?php endforeach; ?>
      </select>
    </label>

    <label>Date found
      <input type="date" name="date_found" max="<?= e($today) ?>" value="<?= e($old['date_found']) ?>">
    </label>

    <label>Description <span class="muted">(optional)</span>
      <textarea name="description" rows="4" maxlength="1000"
                placeholder="Colour, brand, condition. Leave out details only the owner would know, such as serial numbers or what is inside, so an administrator can verify claims."><?= e($old['description']) ?></textarea>
    </label>

    <label>Picture <span class="muted">(optional, JPG, PNG or WEBP, up to 2 MB)</span>
      <input type="file" name="image" accept="image/jpeg,image/png,image/webp">
    </label>

    <button class="btn">Submit found item report</button>
  </form>

  <p class="muted center"><a href="<?= BASE_URL ?>/dashboard.php">Cancel</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>