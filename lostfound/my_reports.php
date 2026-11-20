<?php
// !! My Reports
//    flowchart: Choose Action -> My Reports -> View and Manage Own Reports
//    Shows only the logged-in user's own lost and found reports.
//    Manage = edit (item_edit.php) and delete.
//    Business rule 3: every report belongs to the user who created it,
//    so a user can only ever edit or delete their own.

require __DIR__ . '/includes/config.php';
require_login();

$uid       = $_SESSION['user_id'];
$uploadDir = __DIR__ . '/uploads';

// Fixed list of table and column names, never taken from the visitor
$tables = [
    'Lost'  => ['table' => 'Lost_Item',  'id' => 'lost_item_id'],
    'Found' => ['table' => 'Found_Item', 'id' => 'found_item_id'],
];

$error = null;

// ===================
// HANDLE DELETE
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";
    } else {
        $type = $_POST['type'] ?? '';
        $id   = (int)($_POST['id'] ?? 0);

        if (!isset($tables[$type]) || $id <= 0) {
            $error = "That report could not be found.";
        } else {
            $t = $tables[$type];

            // Only the owner may delete (user_id must match the logged-in user)
            $stmt = $pdo->prepare("SELECT image FROM {$t['table']} WHERE {$t['id']} = ? AND user_id = ?");
            $stmt->execute([$id, $uid]);
            $row = $stmt->fetch();

            if (!$row) {
                $error = "That report could not be found.";
            } else {
                // A found item that already has claims must stay (claims point to it)
                $hasClaims = false;
                if ($type === 'Found') {
                    $chk = $pdo->prepare("SELECT COUNT(*) FROM Claim WHERE found_item_id = ?");
                    $chk->execute([$id]);
                    $hasClaims = (int)$chk->fetchColumn() > 0;
                }

                if ($hasClaims) {
                    $error = "This found item already has claims, so it can't be deleted.";
                } else {
                    $del = $pdo->prepare("DELETE FROM {$t['table']} WHERE {$t['id']} = ? AND user_id = ?");
                    $del->execute([$id, $uid]);

                    // Remove the picture file too (basename keeps it inside uploads/)
                    if ($row['image']) {
                        @unlink($uploadDir . '/' . basename($row['image']));
                    }
                    redirect('my_reports.php?deleted=1');
                }
            }
        }
    }
}

// ===================
// FETCH THIS USER'S REPORTS (Lost + Found in one list)
// ===================
$stmt = $pdo->prepare(
    "SELECT 'Lost' AS type, l.lost_item_id AS item_id, l.item_name, l.date_lost AS item_date,
            l.status, l.date_posted, l.image, 0 AS claim_count, (l.status = 'Lost') AS is_open
     FROM Lost_Item l
     WHERE l.user_id = ?
     UNION ALL
     SELECT 'Found', f.found_item_id, f.item_name, f.date_found,
            f.status, f.date_posted, f.image,
            (SELECT COUNT(*) FROM Claim c WHERE c.found_item_id = f.found_item_id),
            (f.status = 'Available')
     FROM Found_Item f
     WHERE f.user_id = ?
     ORDER BY date_posted DESC"
);
$stmt->execute([$uid, $uid]);
$reports = $stmt->fetchAll();

$pageTitle = 'My reports';
require __DIR__ . '/includes/header.php';
?>

<h1>My reports</h1>
<p class="muted">The lost and found reports you posted.</p>

<?php if (isset($_GET['deleted'])): ?>
  <div class="alert success">Report deleted.</div>
<?php endif; ?>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<p class="muted"><?= count($reports) ?> report<?= count($reports) === 1 ? '' : 's' ?></p>

<?php if (!$reports): ?>
  <div class="card center muted">
    You have not posted any reports yet.<br>
    <a href="<?= BASE_URL ?>/found_add.php">Report a found item</a>
  </div>
<?php endif; ?>

<?php foreach ($reports as $r): ?>
  <div class="card item" style="margin-bottom: 12px;">
    <?php if ($r['image']): ?>
      <img class="thumb" src="<?= BASE_URL ?>/uploads/<?= e($r['image']) ?>" alt="">
    <?php endif; ?>

    <div class="item-body">
      <div class="item-top">
        <span class="badge <?= strtolower($r['type']) ?>"><?= e($r['type']) ?></span>
        <h2><a href="<?= BASE_URL ?>/item_view.php?type=<?= e($r['type']) ?>&amp;id=<?= (int)$r['item_id'] ?>"><?= e($r['item_name']) ?></a></h2>
        <span class="badge <?= $r['is_open'] ? 'open' : 'done' ?>"><?= e($r['status']) ?></span>
      </div>

      <p class="meta">
        <?= $r['type'] === 'Lost' ? 'Lost' : 'Found' ?> on <?= e(date('M j, Y', strtotime($r['item_date']))) ?> &middot;
        Posted <?= e(date('M j, Y', strtotime($r['date_posted']))) ?>
        <?php if ($r['type'] === 'Found'): ?>
          &middot; <?= (int)$r['claim_count'] ?> claim<?= (int)$r['claim_count'] === 1 ? '' : 's' ?>
        <?php endif; ?>
      </p>

      <div style="margin: 10px 0 0; display: flex; align-items: center; gap: 8px;">
        <a href="<?= BASE_URL ?>/item_view.php?type=<?= e($r['type']) ?>&amp;id=<?= (int)$r['item_id'] ?>">View</a>

        <?php if ($r['is_open'] && !((int)$r['claim_count'] > 0)): ?>
          &middot;
          <a href="<?= BASE_URL ?>/item_edit.php?type=<?= e($r['type']) ?>&amp;id=<?= (int)$r['item_id'] ?>">Edit</a>
        <?php endif; ?>

        <?php if ($r['type'] === 'Found' && (int)$r['claim_count'] > 0): ?>
          <span class="muted">&middot; Can't edit or delete (has claims)</span>
        <?php else: ?>
          &middot;
          <form method="POST" class="inline"
                onsubmit="return confirm('Delete this report? This cannot be undone.');">
            <?= csrf_field() ?>
            <input type="hidden" name="type" value="<?= e($r['type']) ?>">
            <input type="hidden" name="id" value="<?= (int)$r['item_id'] ?>">
            <button class="linkbtn">Delete</button>
          </form>
        <?php endif; ?>
      </div>
    </div>
  </div>
<?php endforeach; ?>

<p><a href="<?= BASE_URL ?>/dashboard.php">&larr; Back to dashboard</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>