<?php
// !! Browse Items
//    flowchart: Choose Action -> Browse Items -> View Lost and Found Items
//               -> Search or Filter Items -> Select an Item (item_view.php)

require __DIR__ . '/includes/config.php';
require_login();

$uid     = $_SESSION['user_id'];
$perPage = 10;

// ===================
// GET SEARCH / FILTER INPUT
// ===================
$q       = trim($_GET['q'] ?? '');
$type    = $_GET['type'] ?? '';
if (!in_array($type, ['Lost', 'Found'], true)) $type = '';
$cat     = (int)($_GET['category'] ?? 0);
$loc     = (int)($_GET['location'] ?? 0);
$showAll = isset($_GET['all']);                 // tick-box: also show resolved items
$page    = max(1, (int)($_GET['page'] ?? 1));

// ===================
// DROPDOWN DATA
// ===================
$categories = $pdo->query("SELECT category_id, category_name FROM Category ORDER BY category_name")->fetchAll();
$locations  = $pdo->query("SELECT location_id, location_name FROM Location ORDER BY location_name")->fetchAll();

// ===================
// ONE LIST BUILT FROM TWO TABLES
// Lost_Item and Found_Item have the same kind of columns, so UNION ALL
// stacks them into one list we can search, filter and sort together.
// is_open = 1 means the item is still waiting (Lost / Available).
// ===================
$base = "
  SELECT 'Lost' AS type, l.lost_item_id AS item_id, l.user_id, l.item_name, l.description,
         l.date_lost AS item_date, l.image, l.status, l.date_posted,
         l.category_id, l.location_id, (l.status = 'Lost') AS is_open
  FROM Lost_Item l
  UNION ALL
  SELECT 'Found', f.found_item_id, f.user_id, f.item_name, f.description,
         f.date_found, f.image, f.status, f.date_posted,
         f.category_id, f.location_id, (f.status = 'Available')
  FROM Found_Item f";

$from = "FROM ($base) i
         JOIN Category c  ON c.category_id  = i.category_id
         JOIN Location lo ON lo.location_id = i.location_id";

// ===================
// BUILD THE WHERE PART FROM WHATEVER THE USER FILLED IN
// ===================
$where  = [];
$params = [];

if ($q !== '') {
    // escape % and _ so the user's text is searched literally
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = "(i.item_name LIKE :q1 OR i.description LIKE :q2
                 OR c.category_name LIKE :q3 OR lo.location_name LIKE :q4)";
    foreach ([':q1', ':q2', ':q3', ':q4'] as $p) $params[$p] = $like;
}
if ($type !== '') { $where[] = "i.type = :type";        $params[':type'] = $type; }
if ($cat > 0)     { $where[] = "i.category_id = :cat";  $params[':cat']  = $cat; }
if ($loc > 0)     { $where[] = "i.location_id = :loc";  $params[':loc']  = $loc; }
if (!$showAll)    { $where[] = "i.is_open = 1"; }

$whereSql = $where ? 'WHERE ' . implode(' AND ', $where) : '';

// ===================
// COUNT, THEN FETCH ONE PAGE
// ===================
$stmt = $pdo->prepare("SELECT COUNT(*) $from $whereSql");
$stmt->execute($params);
$total = (int)$stmt->fetchColumn();

$pages  = max(1, (int)ceil($total / $perPage));
$page   = min($page, $pages);
$offset = ($page - 1) * $perPage;          // whole numbers only, so safe to put in the SQL

$stmt = $pdo->prepare(
    "SELECT i.*, c.category_name, lo.location_name, lo.building
     $from $whereSql
     ORDER BY i.date_posted DESC, i.type, i.item_id DESC
     LIMIT $perPage OFFSET $offset"
);
$stmt->execute($params);
$items = $stmt->fetchAll();

// Link to another page of results while keeping the current search and filters
function page_url(int $p): string {
    $query = $_GET;
    $query['page'] = $p;
    return BASE_URL . '/items.php?' . http_build_query($query);
}

$pageTitle = 'Browse items';
require __DIR__ . '/includes/header.php';
?>

<h1>Browse items</h1>
<p class="muted">Search the lost and found reports posted by everyone.</p>

<!-- =================== -->
<!-- SEARCH + FILTERS -->
<!-- =================== -->
<form method="GET" class="filters card">
  <input name="q" placeholder="Search item, category or place" value="<?= e($q) ?>">

  <select name="type">
    <option value="">All types</option>
    <option value="Lost"  <?= $type === 'Lost'  ? 'selected' : '' ?>>Lost</option>
    <option value="Found" <?= $type === 'Found' ? 'selected' : '' ?>>Found</option>
  </select>

  <select name="category">
    <option value="0">All categories</option>
    <?php foreach ($categories as $c): ?>
      <option value="<?= (int)$c['category_id'] ?>" <?= $cat === (int)$c['category_id'] ? 'selected' : '' ?>>
        <?= e($c['category_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <select name="location">
    <option value="0">All locations</option>
    <?php foreach ($locations as $l): ?>
      <option value="<?= (int)$l['location_id'] ?>" <?= $loc === (int)$l['location_id'] ? 'selected' : '' ?>>
        <?= e($l['location_name']) ?>
      </option>
    <?php endforeach; ?>
  </select>

  <label class="check">
    <input type="checkbox" name="all" value="1" <?= $showAll ? 'checked' : '' ?>>
    Include resolved items (returned, claimed, cancelled)
  </label>

  <div class="filter-actions">
    <button class="btn small">Search</button>
    <a class="reset" href="<?= BASE_URL ?>/items.php">Reset</a>
  </div>
</form>

<p class="muted"><?= $total ?> item<?= $total === 1 ? '' : 's' ?> found</p>

<!-- =================== -->
<!-- RESULTS -->
<!-- =================== -->
<?php if (!$items): ?>
  <div class="card center muted">No items match your search.</div>
<?php endif; ?>

<?php foreach ($items as $it): ?>
  <a class="card item" href="<?= BASE_URL ?>/item_view.php?type=<?= e($it['type']) ?>&amp;id=<?= (int)$it['item_id'] ?>">
    <?php if ($it['image']): ?>
      <img class="thumb" src="<?= BASE_URL ?>/uploads/<?= e($it['image']) ?>" alt="">
    <?php endif; ?>
    <div class="item-body">
      <div class="item-top">
        <span class="badge <?= strtolower($it['type']) ?>"><?= e($it['type']) ?></span>
        <h2><?= e($it['item_name']) ?></h2>
        <span class="badge <?= $it['is_open'] ? 'open' : 'done' ?>"><?= e($it['status']) ?></span>
        <?php if ((int)$it['user_id'] === $uid): ?><span class="badge mine">Your report</span><?php endif; ?>
      </div>
      <?php if ($it['description']): ?>
        <p class="desc"><?= e(mb_strimwidth($it['description'], 0, 120, '…')) ?></p>
      <?php endif; ?>
      <p class="meta">
        <?= e($it['category_name']) ?> &middot;
        <?= e($it['location_name']) ?><?= $it['building'] ? ' (' . e($it['building']) . ')' : '' ?> &middot;
        <?= $it['type'] === 'Lost' ? 'Lost' : 'Found' ?> on <?= e(date('M j, Y', strtotime($it['item_date']))) ?>
      </p>
    </div>
  </a>
<?php endforeach; ?>

<!-- =================== -->
<!-- PAGE LINKS -->
<!-- =================== -->
<?php if ($pages > 1): ?>
  <div class="pager">
    <?php if ($page > 1): ?><a href="<?= e(page_url($page - 1)) ?>">&larr; Previous</a><?php endif; ?>
    <span>Page <?= $page ?> of <?= $pages ?></span>
    <?php if ($page < $pages): ?><a href="<?= e(page_url($page + 1)) ?>">Next &rarr;</a><?php endif; ?>
  </div>
<?php endif; ?>

<?php require __DIR__ . '/includes/footer.php'; ?>