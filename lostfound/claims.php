<?php
// !! View Claim Status
//    flowchart: Submit Claim Request -> Wait for Admin Review -> View Claim Status
//    Shows only the claims made by the logged-in user.

require __DIR__ . '/includes/config.php';
require_login();

$uid = $_SESSION['user_id'];

// ===================
// FETCH THIS USER'S CLAIMS
// JOIN: Claim + Found_Item + Category + Location (+ Admin once someone reviewed it)
// ===================
$stmt = $pdo->prepare(
    "SELECT cl.claim_id, cl.found_item_id, cl.claim_description, cl.date_submitted, cl.claim_status,
            f.item_name, c.category_name, lo.location_name, lo.building,
            a.full_name AS admin_name
     FROM Claim cl
     JOIN Found_Item f ON f.found_item_id = cl.found_item_id
     JOIN Category c   ON c.category_id   = f.category_id
     JOIN Location lo  ON lo.location_id  = f.location_id
     LEFT JOIN Admin a ON a.admin_id      = cl.admin_id
     WHERE cl.user_id = ?
     ORDER BY cl.date_submitted DESC, cl.claim_id DESC"
);
$stmt->execute([$uid]);
$claims = $stmt->fetchAll();

// What each status means to the user
$statusText = [
    'Pending'  => 'Waiting for an administrator to review your claim.',
    'Approved' => 'An administrator approved your claim.',
    'Rejected' => 'An administrator rejected your claim.',
];

$pageTitle = 'My claims';
require __DIR__ . '/includes/header.php';
?>

<h1>My claims</h1>
<p class="muted">Track the claims you sent for found items.</p>

<?php if (isset($_GET['submitted'])): ?>
  <div class="alert success">Your claim was submitted. An administrator will review it.</div>
<?php endif; ?>

<p class="muted"><?= count($claims) ?> claim<?= count($claims) === 1 ? '' : 's' ?></p>

<?php if (!$claims): ?>
  <div class="card center muted">
    You have not submitted any claims yet.<br>
    <a href="<?= BASE_URL ?>/items.php">Browse items</a> to find something that is yours.
  </div>
<?php endif; ?>

<?php foreach ($claims as $cl): ?>
  <div class="card" style="margin-bottom: 12px;">
    <div class="item-top">
      <span class="badge found">Found</span>
      <h2><a href="<?= BASE_URL ?>/item_view.php?type=Found&amp;id=<?= (int)$cl['found_item_id'] ?>"><?= e($cl['item_name']) ?></a></h2>
      <span class="badge <?= e(strtolower($cl['claim_status'])) ?>"><?= e($cl['claim_status']) ?></span>
    </div>

    <p class="meta">
      <?= e($cl['category_name']) ?> &middot;
      <?= e($cl['location_name']) ?><?= $cl['building'] ? ' (' . e($cl['building']) . ')' : '' ?> &middot;
      Claimed on <?= e(date('M j, Y', strtotime($cl['date_submitted']))) ?>
    </p>

    <p class="desc"><strong>Your proof:</strong> <?= nl2br(e($cl['claim_description'])) ?></p>

    <p class="muted">
      <?= e($statusText[$cl['claim_status']] ?? '') ?>
      <?php if ($cl['admin_name']): ?>Reviewed by <?= e($cl['admin_name']) ?>.<?php endif; ?>
    </p>
  </div>
<?php endforeach; ?>

<p><a href="<?= BASE_URL ?>/dashboard.php">&larr; Back to dashboard</a></p>

<?php require __DIR__ . '/includes/footer.php'; ?>