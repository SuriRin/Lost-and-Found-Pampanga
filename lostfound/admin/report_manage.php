<?php
// !! Manage One Lost or Found Report
//    flowchart: View Item Reports -> Manage Lost and Found Reports
//    The admin can change a report's status (for example mark a found item as Returned
//    after the handover) or delete it. The report's text stays the user's own words.

require __DIR__ . '/../includes/config.php';
require_admin();

$type      = $_GET['type'] ?? '';
$id        = (int)($_GET['id'] ?? 0);
$uploadDir = __DIR__ . '/../uploads';

// Fixed list of table and column names, never taken from the visitor
$tables = [
    'Lost'  => ['table' => 'Lost_Item',  'id' => 'lost_item_id',  'date' => 'date_lost',
                'open' => 'Lost',      'statuses' => ['Lost', 'Found', 'Cancelled']],
    'Found' => ['table' => 'Found_Item', 'id' => 'found_item_id', 'date' => 'date_found',
                'open' => 'Available', 'statuses' => ['Available', 'Claimed', 'Returned']],
];

// ===================
// FETCH THE REPORT
// ===================
$item = false;
if (isset($tables[$type]) && $id > 0) {
    $t = $tables[$type];
    $stmt = $pdo->prepare(
        "SELECT t.user_id, t.item_name, t.description, t.{$t['date']} AS item_date, t.image,
                t.status, t.date_posted,
                c.category_name, lo.location_name, lo.building,
                u.full_name AS reporter, u.email AS reporter_email, u.contact_number AS reporter_phone
         FROM {$t['table']} t
         JOIN Category c  ON c.category_id  = t.category_id
         JOIN Location lo ON lo.location_id = t.location_id
         JOIN `User` u    ON u.user_id      = t.user_id
         WHERE t.{$t['id']} = ?"
    );
    $stmt->execute([$id]);
    $item = $stmt->fetch();
}

// ===================
// REPORT NOT FOUND
// ===================
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Report not found';
    require __DIR__ . '/../includes/admin_header.php';
    ?>
    <div class="card narrow center">
      <h1>Report not found</h1>
      <p class="muted">That report does not exist or was removed.</p>
      <p><a href="<?= BASE_URL ?>/admin/reports.php">&larr; Back to reports</a></p>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

// ===================
// CLAIMS ON THIS ITEM (found items only)
// ===================
$claims = [];
if ($type === 'Found') {
    $stmt = $pdo->prepare(
        "SELECT cl.claim_id, cl.claim_status, cl.date_submitted, u.full_name AS claimant
         FROM Claim cl
         JOIN `User` u ON u.user_id = cl.user_id
         WHERE cl.found_item_id = ?
         ORDER BY cl.date_submitted DESC, cl.claim_id DESC"
    );
    $stmt->execute([$id]);
    $claims = $stmt->fetchAll();
}
$claimCount    = count($claims);
$approvedCount = count(array_filter($claims, fn($c) => $c['claim_status'] === 'Approved'));

$error = null;

// ===================
// HANDLE CHANGE STATUS / DELETE
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";

    } elseif ($action === 'status') {
        $new = $_POST['new_status'] ?? '';

        if (!in_array($new, $t['statuses'], true)) {
            $error = "Please choose a valid status.";
        } elseif ($new === $item['status']) {
            $error = "The report already has that status.";
        } elseif ($type === 'Found' && $new === 'Available' && $approvedCount > 0) {
            // Keep the claim and the item in step with each other
            $error = "This item has an approved claim, so it can't go back to Available.";
        } elseif ($type === 'Found' && $new === 'Claimed' && $approvedCount === 0) {
            $error = "Claimed is set by approving a claim. Approve one in Manage claims first.";
        } else {
            $stmt = $pdo->prepare("UPDATE {$t['table']} SET status = ? WHERE {$t['id']} = ?");
            $stmt->execute([$new, $id]);
            redirect('admin/report_manage.php?type=' . $type . '&id=' . $id . '&updated=1');
        }

    } elseif ($action === 'delete') {
        // A found item that has claims must stay (the claims point to it)
        if ($claimCount > 0) {
            $error = "This found item has claims, so it can't be deleted.";
        } else {
            $stmt = $pdo->prepare("DELETE FROM {$t['table']} WHERE {$t['id']} = ?");
            $stmt->execute([$id]);

            // Remove the picture file too (basename keeps it inside uploads/)
            if ($item['image']) {
                @unlink($uploadDir . '/' . basename($item['image']));
            }
            redirect('admin/reports.php?done=deleted');
        }
    }
}

$isOpen = $item['status'] === $t['open'];

$pageTitle = $item['item_name'];
require __DIR__ . '/../includes/admin_header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/reports.php">&larr; Back to reports</a></p>

<?php if (isset($_GET['updated'])): ?>
  <div class="alert success">Status updated.</div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- =================== -->
<!-- THE REPORT -->
<!-- =================== -->
<div class="card">
  <div class="item-top">
    <span class="badge <?= strtolower($type) ?>"><?= e($type) ?></span>
    <h1><?= e($item['item_name']) ?></h1>
    <span class="badge <?= $isOpen ? 'open' : 'done' ?>"><?= e($item['status']) ?></span>
  </div>

  <?php if ($item['image']): ?>
    <img class="detail-img" src="<?= BASE_URL ?>/uploads/<?= e($item['image']) ?>" alt="Photo of <?= e($item['item_name']) ?>">
  <?php endif; ?>

  <dl class="details">
    <dt>Description</dt>
    <dd><?= $item['description'] ? nl2br(e($item['description'])) : '<span class="muted">No description given.</span>' ?></dd>

    <dt>Category</dt>
    <dd><?= e($item['category_name']) ?></dd>

    <dt>Location</dt>
    <dd><?= e($item['location_name']) ?><?= $item['building'] ? ' (' . e($item['building']) . ')' : '' ?></dd>

    <dt><?= $type === 'Lost' ? 'Date lost' : 'Date found' ?></dt>
    <dd><?= e(date('F j, Y', strtotime($item['item_date']))) ?></dd>

    <dt>Reported by</dt>
    <dd>
      <?= e($item['reporter']) ?>
      <span class="muted">on <?= e(date('M j, Y', strtotime($item['date_posted']))) ?></span><br>
      <span class="muted"><?= e($item['reporter_email']) ?><?= $item['reporter_phone'] ? ' &middot; ' . e($item['reporter_phone']) : '' ?></span>
    </dd>
  </dl>
</div>

<!-- =================== -->
<!-- CLAIMS ON THIS ITEM -->
<!-- =================== -->
<?php if ($type === 'Found'): ?>
  <div class="card">
    <h2>Claims (<?= $claimCount ?>)</h2>
    <?php if (!$claims): ?>
      <p class="muted">Nobody has claimed this item yet.</p>
    <?php else: ?>
      <?php foreach ($claims as $cl): ?>
        <p style="margin: 10px 0 0;">
          <span class="badge <?= e(strtolower($cl['claim_status'])) ?>" style="margin-left: 0;"><?= e($cl['claim_status']) ?></span>
          <a href="<?= BASE_URL ?>/admin/claim_review.php?id=<?= (int)$cl['claim_id'] ?>"><?= e($cl['claimant']) ?></a>
          <span class="muted">&middot; <?= e(date('M j, Y', strtotime($cl['date_submitted']))) ?></span>
        </p>
      <?php endforeach; ?>
    <?php endif; ?>
  </div>
<?php endif; ?>

<!-- =================== -->
<!-- MANAGE: CHANGE STATUS -->
<!-- =================== -->
<div class="card">
  <h2>Change status</h2>
  <?php if ($type === 'Found'): ?>
    <p class="muted">Use <strong>Returned</strong> once the owner has collected the item. <strong>Claimed</strong> is set by approving a claim.</p>
  <?php else: ?>
    <p class="muted">Use <strong>Found</strong> when the owner got the item back, or <strong>Cancelled</strong> to close the report.</p>
  <?php endif; ?>

  <form method="POST" style="max-width: 320px;">
    <?= csrf_field() ?>
    <input type="hidden" name="action" value="status">

    <label>Status
      <select name="new_status">
        <?php foreach ($t['statuses'] as $s): ?>
          <option value="<?= e($s) ?>" <?= $item['status'] === $s ? 'selected' : '' ?>><?= e($s) ?></option>
        <?php endforeach; ?>
      </select>
    </label>

    <button class="btn small" style="margin-top: 14px;">Update status</button>
  </form>
</div>

<!-- =================== -->
<!-- MANAGE: DELETE -->
<!-- =================== -->
<div class="card">
  <h2>Delete report</h2>
  <?php if ($claimCount > 0): ?>
    <p class="muted">This found item has claims, so it can't be deleted.</p>
  <?php else: ?>
    <p class="muted">Removes the report and its picture. This cannot be undone.</p>
    <form method="POST" class="inline"
          onsubmit="return confirm('Delete this report? This cannot be undone.');">
      <?= csrf_field() ?>
      <input type="hidden" name="action" value="delete">
      <button class="btn small" style="background: var(--danger);">Delete report</button>
    </form>
  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>