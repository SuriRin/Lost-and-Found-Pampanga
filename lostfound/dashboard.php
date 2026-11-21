<?php
// !! User Dashboard  (flowchart: Choose Action)

require __DIR__ . '/includes/config.php';
require_login();

$uid = $_SESSION['user_id'];

// ===================
// QUICK COUNTS FOR THIS USER
// ===================
$counts = [];
foreach ([
    'lost'   => "SELECT COUNT(*) FROM Lost_Item  WHERE user_id = ?",
    'found'  => "SELECT COUNT(*) FROM Found_Item WHERE user_id = ?",
    'claims' => "SELECT COUNT(*) FROM Claim      WHERE user_id = ?",
] as $key => $sql) {
    $stmt = $pdo->prepare($sql);
    $stmt->execute([$uid]);
    $counts[$key] = (int)$stmt->fetchColumn();
}

// ===================
// ACTIONS FROM THE FLOWCHART
// Set 'ready' to true when that page has been built.
// ===================
$actions = [
    ['Browse Items',       'Search lost and found items posted by others.', 'items.php',      true],
    ['Report Lost Item',   'Tell us about something you lost.',             'lost_add.php',   true],
    ['Report Found Item',  'Post something you found so its owner can claim it.', 'found_add.php', true],
    ['My Reports',         'View, update or delete your own reports.',      'my_reports.php', true],
    ['My Claims',          'Track the claims you submitted and see if they were approved.', 'claims.php', true],
];

$pageTitle = 'Dashboard';
require __DIR__ . '/includes/header.php';
?>

<h1>Hello, <?= e($_SESSION['user_name']) ?></h1>
<p class="muted">What would you like to do?</p>

<!-- =================== -->
<!-- STATS -->
<!-- =================== -->
<div class="stats">
  <div class="stat"><strong><?= $counts['lost'] ?></strong><span>Lost reports</span></div>
  <div class="stat"><strong><?= $counts['found'] ?></strong><span>Found reports</span></div>
  <div class="stat"><strong><?= $counts['claims'] ?></strong><span>Claims submitted</span></div>
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

<?php require __DIR__ . '/includes/footer.php'; ?>