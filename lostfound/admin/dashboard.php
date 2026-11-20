<?php
// !! Admin Dashboard  (flowchart: Choose Action)
//    Manage Reports -> View Item Reports -> Manage Lost and Found Reports
//    Manage Claims  -> View Pending Claims -> Review Claim Details -> Approve Claim?
//    Logout -> END

require __DIR__ . '/../includes/config.php';
require_admin();

// ===================
// QUICK COUNTS
// ===================
$counts = [
    'pending' => (int)$pdo->query("SELECT COUNT(*) FROM Claim WHERE claim_status = 'Pending'")->fetchColumn(),
    'found'   => (int)$pdo->query("SELECT COUNT(*) FROM Found_Item WHERE status = 'Available'")->fetchColumn(),
    'lost'    => (int)$pdo->query("SELECT COUNT(*) FROM Lost_Item WHERE status = 'Lost'")->fetchColumn(),
];

// ===================
// ACTIONS FROM THE FLOWCHART
// Set 'ready' to true when that page has been built.
// ===================
$actions = [
    ['Manage Reports', 'View and manage all lost and found reports.', 'admin/reports.php', true],
    ['Manage Claims',  'Review pending claims, then approve or reject them.', 'admin/claims.php', true],
];

$pageTitle = 'Admin dashboard';
require __DIR__ . '/../includes/admin_header.php';
?>

<h1>Hello, <?= e($_SESSION['admin_name']) ?></h1>
<p class="muted">What would you like to do?</p>

<!-- =================== -->
<!-- STATS -->
<!-- =================== -->
<div class="stats">
  <div class="stat"><strong><?= $counts['pending'] ?></strong><span>Pending claims</span></div>
  <div class="stat"><strong><?= $counts['found'] ?></strong><span>Available found items</span></div>
  <div class="stat"><strong><?= $counts['lost'] ?></strong><span>Open lost reports</span></div>
</div>

<!-- =================== -->
<!-- ACTION CARDS -->
<!-- =================== -->
<div class="grid">
  <?php foreach ($actions as [$title, $text, $file, $ready]): ?>
    <?php if ($ready): ?>
      <a class="card action" href="<?= BASE_URL ?>/<?= e($file) ?>">
        <h2><?= e($title) ?></h2>
        <p><?= e($text) ?></p>
      </a>
    <?php else: ?>
      <div class="card action disabled">
        <h2><?= e($title) ?> <span class="badge">Coming soon</span></h2>
        <p><?= e($text) ?></p>
      </div>
    <?php endif; ?>
  <?php endforeach; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>