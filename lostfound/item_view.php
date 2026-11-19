<?php
// !! View one item
//    flowchart: Select an Item -> Is It a Found Item?
//                 Yes -> Submit Claim Request (built in a later step)
//                 No  -> nothing more to do, back to the dashboard

require __DIR__ . '/includes/config.php';
require_login();

$uid  = $_SESSION['user_id'];
$type = $_GET['type'] ?? '';
$id   = (int)($_GET['id'] ?? 0);


$claimPageReady = true;    // claim_add.php   
$foundPageReady = true;    // found_add.php  

// ===================
// WHICH TABLE TO READ
// Table and column names come from this fixed list, never from the visitor,
// so it is safe to put them in the SQL.
// ===================
$tables = [
    'Lost'  => ['table' => 'Lost_Item',  'id' => 'lost_item_id',  'date' => 'date_lost',  'open' => 'Lost'],
    'Found' => ['table' => 'Found_Item', 'id' => 'found_item_id', 'date' => 'date_found', 'open' => 'Available'],
];

$item = false;
if (isset($tables[$type]) && $id > 0) {
    $t = $tables[$type];
    $stmt = $pdo->prepare(
        "SELECT t.user_id, t.item_name, t.description, t.{$t['date']} AS item_date, t.image,
                t.status, t.date_posted,
                c.category_name, lo.location_name, lo.building, lo.description AS location_description,
                u.full_name AS reporter
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
// ITEM NOT FOUND
// ===================
if (!$item) {
    http_response_code(404);
    $pageTitle = 'Item not found';
    require __DIR__ . '/includes/header.php';
    ?>
    <div class="card narrow center">
      <h1>Item not found</h1>
      <p class="muted">That report does not exist or was removed.</p>
      <p><a href="<?= BASE_URL ?>/items.php">&larr; Back to browse</a></p>
    </div>
    <?php
    require __DIR__ . '/includes/footer.php';
    exit;
}

$isOwn  = (int)$item['user_id'] === $uid;
$isOpen = $item['status'] === $t['open'];

// Has this user already claimed this found item?
$myClaim = null;
if ($type === 'Found') {
    $stmt = $pdo->prepare(
        "SELECT claim_status, date_submitted FROM Claim
         WHERE found_item_id = ? AND user_id = ?
         ORDER BY date_submitted DESC LIMIT 1"
    );
    $stmt->execute([$id, $uid]);
    $myClaim = $stmt->fetch() ?: null;
}

$pageTitle = $item['item_name'];
require __DIR__ . '/includes/header.php';
?>

<p><a href="<?= BASE_URL ?>/items.php">&larr; Back to browse</a></p>

<div class="card">
  <div class="item-top">
    <span class="badge <?= strtolower($type) ?>"><?= e($type) ?></span>
    <h1><?= e($item['item_name']) ?></h1>
    <span class="badge <?= $isOpen ? 'open' : 'done' ?>"><?= e($item['status']) ?></span>
    <?php if ($isOwn): ?><span class="badge mine">Your report</span><?php endif; ?>
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
    <dd>
      <?= e($item['location_name']) ?><?= $item['building'] ? ' (' . e($item['building']) . ')' : '' ?>
      <?php if ($item['location_description']): ?><br><span class="muted"><?= e($item['location_description']) ?></span><?php endif; ?>
    </dd>

    <dt><?= $type === 'Lost' ? 'Date lost' : 'Date found' ?></dt>
    <dd><?= e(date('F j, Y', strtotime($item['item_date']))) ?></dd>

    <dt>Reported by</dt>
    <dd><?= e($item['reporter']) ?> <span class="muted">on <?= e(date('M j, Y', strtotime($item['date_posted']))) ?></span></dd>
  </dl>
</div>

<!-- =================== -->
<!-- "IS IT A FOUND ITEM?" BRANCH OF THE FLOWCHART -->
<!-- =================== -->
<div class="card">
<?php if ($type === 'Found'): ?>

  <?php if ($myClaim): ?>
    <h2>Your claim</h2>
    <p>You submitted a claim on <?= e(date('M j, Y', strtotime($myClaim['date_submitted']))) ?>.
       Status: <span class="badge <?= $myClaim['claim_status'] === 'Approved' ? 'open' : 'done' ?>"><?= e($myClaim['claim_status']) ?></span></p>

  <?php elseif ($isOwn): ?>
    <h2>This is your report</h2>
    <p class="muted">You cannot claim an item you reported as found.</p>

  <?php elseif (!$isOpen): ?>
    <h2>No longer available</h2>
    <p class="muted">This item is already marked as <?= e(strtolower($item['status'])) ?>, so it can't be claimed.</p>

  <?php else: ?>
    <h2>Is this yours?</h2>
    <p class="muted">Send a claim with proof of ownership. An administrator will review it.</p>
    <?php if ($claimPageReady): ?>
      <a class="btn small" href="<?= BASE_URL ?>/claim_add.php?id=<?= (int)$id ?>">Claim this item</a>
    <?php else: ?>
      <span class="btn small disabled">Claim this item <span class="badge">Coming soon</span></span>
    <?php endif; ?>
  <?php endif; ?>

<?php else: ?>

  <h2>Did you find this item?</h2>
  <p class="muted">Lost items can't be claimed. If you found something that looks like this, report it as a found item so its owner can claim it.</p>
  <?php if ($foundPageReady): ?>
    <a class="btn small" href="<?= BASE_URL ?>/found_add.php">Report a found item</a>
  <?php else: ?>
    <span class="btn small disabled">Report a found item <span class="badge">Coming soon</span></span>
  <?php endif; ?>
  <a class="reset" href="<?= BASE_URL ?>/dashboard.php">Back to dashboard</a>

<?php endif; ?>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>