<?php
// !! Manage Claims: View Pending Claims
//    flowchart: Choose Action -> Manage Claims -> View Pending Claims -> Review Claim Details
//    Shows pending claims by default. The tabs let the admin look at decided claims too.

require __DIR__ . '/../includes/config.php';
require_admin();

$allowed = ['Pending', 'Approved', 'Rejected'];
$status  = $_GET['status'] ?? 'Pending';
if (!in_array($status, $allowed, true)) $status = 'Pending';

// ===================
// COUNT PER STATUS (for the tabs)
// ===================
$counts = array_fill_keys($allowed, 0);
foreach ($pdo->query("SELECT claim_status, COUNT(*) AS n FROM Claim GROUP BY claim_status") as $r) {
    $counts[$r['claim_status']] = (int)$r['n'];
}

// ===================
// FETCH THE CLAIMS FOR THE CHOSEN STATUS
// Oldest first, so the claims that have waited longest are on top.
// ===================
$stmt = $pdo->prepare(
    "SELECT cl.claim_id, cl.claim_status, cl.claim_description, cl.date_submitted,
            f.item_name, c.category_name,
            u.full_name AS claimant,
            a.full_name AS admin_name
     FROM Claim cl
     JOIN Found_Item f ON f.found_item_id = cl.found_item_id
     JOIN Category c   ON c.category_id   = f.category_id
     JOIN `User` u     ON u.user_id       = cl.user_id
     LEFT JOIN Admin a ON a.admin_id      = cl.admin_id
     WHERE cl.claim_status = ?
     ORDER BY cl.date_submitted ASC, cl.claim_id ASC"
);
$stmt->execute([$status]);
$claims = $stmt->fetchAll();

$pageTitle = 'Manage claims';
require __DIR__ . '/../includes/admin_header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/dashboard.php">&larr; Back to dashboard</a></p>

<h1>Manage claims</h1>
<p class="muted">Open a claim to review the details, then approve or reject it.</p>

<?php if (($_GET['done'] ?? '') === 'approved'): ?>
  <div class="alert success">Claim approved. The item is now marked as claimed.</div>
<?php elseif (($_GET['done'] ?? '') === 'rejected'): ?>
  <div class="alert success">Claim rejected.</div>
<?php endif; ?>

<!-- =================== -->
<!-- STATUS TABS -->
<!-- =================== -->
<div style="display: flex; gap: 16px; margin: 18px 0 14px;">
  <?php foreach ($allowed as $s): ?>
    <?php if ($s === $status): ?>
      <strong><?= e($s) ?> (<?= $counts[$s] ?>)</strong>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/admin/claims.php?status=<?= e($s) ?>"><?= e($s) ?> (<?= $counts[$s] ?>)</a>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<?php if (!$claims): ?>
  <div class="card center muted">No <?= e(strtolower($status)) ?> claims.</div>
<?php endif; ?>

<?php foreach ($claims as $cl): ?>
  <a class="card item" href="<?= BASE_URL ?>/admin/claim_review.php?id=<?= (int)$cl['claim_id'] ?>">
    <div class="item-body">
      <div class="item-top">
        <span class="badge found">Found</span>
        <h2><?= e($cl['item_name']) ?></h2>
        <span class="badge <?= e(strtolower($cl['claim_status'])) ?>"><?= e($cl['claim_status']) ?></span>
      </div>
      <p class="desc"><strong>Proof:</strong> <?= e(mb_strimwidth($cl['claim_description'], 0, 120, '…')) ?></p>
      <p class="meta">
        Claimed by <?= e($cl['claimant']) ?> &middot;
        <?= e($cl['category_name']) ?> &middot;
        Submitted <?= e(date('M j, Y', strtotime($cl['date_submitted']))) ?>
        <?php if ($cl['admin_name']): ?>&middot; Reviewed by <?= e($cl['admin_name']) ?><?php endif; ?>
      </p>
    </div>
  </a>
<?php endforeach; ?>

<?php require __DIR__ . '/../includes/footer.php'; ?>