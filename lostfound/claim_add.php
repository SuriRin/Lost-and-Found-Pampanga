<?php
// !! Submit Claim Request
//    flowchart: Is It a Found Item? -> Yes -> Submit Claim Request
//               -> Wait for Admin Review -> View Claim Status
//    Business rules: 1 (must be logged in), 7 (claims are only for Found Items)
//                    8 (the admin reviews the claim later, so it starts as Pending)

require __DIR__ . '/includes/config.php';
require_login();

$uid = $_SESSION['user_id'];
$id  = (int)($_GET['id'] ?? 0);

// Switch this to true when claims.php exists (step 7, View Claim Status).
// Until then the user is sent back to the item page, which already shows the claim status.
$claimsPageReady = true;

// ===================
// FETCH THE FOUND ITEM BEING CLAIMED
// ===================
$item = false;
if ($id > 0) {
    $stmt = $pdo->prepare(
        "SELECT f.found_item_id, f.user_id, f.item_name, f.date_found, f.status,
                c.category_name, lo.location_name, lo.building
         FROM Found_Item f
         JOIN Category c  ON c.category_id  = f.category_id
         JOIN Location lo ON lo.location_id = f.location_id
         WHERE f.found_item_id = ?"
    );
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

// ===================
// ITEM NOT FOUND
// ===================
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Item not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card narrow center">
      <h1>Item not found</h1>
      <p class="muted">That found item does not exist or was removed.</p>
      <p><a href="<?= BASE_URL ?>/items.php">&larr; Back to browse</a></p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

// ===================
// CAN THIS USER CLAIM IT?
// $blocked holds the reason when the answer is no.
// ===================
$blocked = null;

if ((int)$item['user_id'] === $uid) {
    $blocked = "You reported this item as found, so you cannot claim it.";
} elseif ($item['status'] !== 'Available') {
    $blocked = "This item is no longer available for claims (" . $item['status'] . ").";
} else {
    // One claim per user per item
    $chk = $pdo->prepare("SELECT claim_status FROM Claim WHERE found_item_id = ? AND user_id = ? LIMIT 1");
    $chk->execute([$id, $uid]);
    $existing = $chk->fetch();
    if ($existing) {
        $blocked = "You already submitted a claim for this item. Its status is " . $existing['claim_status'] . ".";
    }
}

// ===================
// SETUP VARIABLES
// ===================
$errors = [];
$claimText = '';

// ===================
// HANDLE FORM SUBMIT
// ===================
if (!$blocked && $_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        $claimText = trim($_POST['claim_description'] ?? '');

        // ===================
        // VALIDATION
        // ===================
        if ($claimText === '') {
            $errors[] = "Please describe how you can prove this item is yours.";
        } elseif (mb_strlen($claimText) < 10) {
            $errors[] = "Please give more detail (at least 10 characters).";
        } elseif (mb_strlen($claimText) > 1000) {
            $errors[] = "Your explanation must be 1000 characters or less.";
        }

        // ===================
        // INSERT (CREATE)
        // admin_id stays NULL and claim_status defaults to 'Pending' until an admin reviews it
        // ===================
        if (!$errors) {
            $stmt = $pdo->prepare(
                "INSERT INTO Claim (found_item_id, user_id, claim_description)
                 VALUES (?, ?, ?)"
            );
            $stmt->execute([$id, $uid, $claimText]);

            // Flowchart: Submit Claim Request -> Wait for Admin Review -> View Claim Status
            redirect($claimsPageReady
                ? 'claims.php?submitted=1'
                : 'item_view.php?type=Found&id=' . $id);
        }
    }
}

$pageTitle = 'Claim item';
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/item_view.php?type=Found&amp;id=<?= (int)$id ?>">&larr; Back to item</a></p>

<div class="card narrow">
  <h1>Claim this item</h1>

  <dl class="details">
    <dt>Item</dt>
    <dd><?= e($item['item_name']) ?></dd>

    <dt>Category</dt>
    <dd><?= e($item['category_name']) ?></dd>

    <dt>Found at</dt>
    <dd><?= e($item['location_name']) ?><?= $item['building'] ? ' (' . e($item['building']) . ')' : '' ?></dd>

    <dt>Date found</dt>
    <dd><?= e(date('F j, Y', strtotime($item['date_found']))) ?></dd>
  </dl>

  <?php if ($blocked): ?>

    <div class="alert error"><?= e($blocked) ?></div>
    <p class="center"><a href="<?= BASE_URL ?>/items.php">Back to browse</a></p>

  <?php else: ?>

    <p class="muted">Explain how you can prove this item is yours. An administrator will review your claim.</p>

    <?php if ($errors): ?>
      <div class="alert error">
        <?php foreach ($errors as $err): ?>
          <?= e($err) ?><br>
        <?php endforeach; ?>
      </div>
    <?php endif; ?>

    <form method="POST" novalidate>
      <?= csrf_field() ?>

      <label>Proof of ownership
        <textarea name="claim_description" rows="5" maxlength="1000"
                  placeholder="Describe things only the owner would know: contents, marks, serial number, lock screen, when and where you lost it."><?= e($claimText) ?></textarea>
      </label>

      <button class="btn">Submit claim request</button>
    </form>

    <p class="muted center"><a href="<?= BASE_URL ?>/item_view.php?type=Found&amp;id=<?= (int)$id ?>">Cancel</a></p>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>