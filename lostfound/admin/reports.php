<?php
// !! Manage Reports: View Item Reports
//    flowchart: Choose Action -> Manage Reports -> View Item Reports
//               -> Manage Lost and Found Reports (report_manage.php)
//    Shows the reports of every user, resolved ones included.

require __DIR__ . '/../includes/config.php';
require_admin();

$perPage = 10;

// ===================
// GET SEARCH / FILTER INPUT
// ===================
$q      = trim($_GET['q'] ?? '');
$type   = $_GET['type'] ?? '';
if (!in_array($type, ['Lost', 'Found'], true)) $type = '';

$statuses = ['Lost', 'Found', 'Cancelled', 'Available', 'Claimed', 'Returned'];
$status   = $_GET['status'] ?? '';
if (!in_array($status, $statuses, true)) $status = '';

$page = max(1, (int)($_GET['page'] ?? 1));

// ===================
// ONE LIST BUILT FROM TWO TABLES (same idea as Browse items)
// ===================
$base = "
  SELECT 'Lost' AS type, l.lost_item_id AS item_id, l.user_id, l.item_name, l.description,
         l.date_lost AS item_date, l.image, l.status, l.date_posted,
         l.category_id, l.location_id
  FROM Lost_Item l
  UNION ALL
  SELECT 'Found', f.found_item_id, f.user_id, f.item_name, f.description,
         f.date_found, f.image, f.status, f.date_posted,
         f.category_id, f.location_id
  FROM Found_Item f";

$from = "FROM ($base) i
         JOIN Category c  ON c.category_id  = i.category_id
         JOIN Location lo ON lo.location_id = i.location_id
         JOIN `User` u    ON u.user_id      = i.user_id";

// ===================
// BUILD THE WHERE PART FROM WHATEVER THE ADMIN FILLED IN
// ===================
$where  = [];
$params = [];

if ($q !== '') {
    // escape % and _ so the text is searched literally
    $like = '%' . addcslashes($q, '%_\\') . '%';
    $where[] = "(i.item_name LIKE :q1 OR i.description LIKE :q2
                 OR c.category_name LIKE :q3 OR lo.location_name LIKE :q4
                 OR u.full_name LIKE :q5)";
    foreach ([':q1', ':q2', ':q3', ':q4', ':q5'] as $p) $params[$p] = $like;
}
if ($type !== '')   { $where[] = "i.type = :type";     $params[':type']   = $type; }
if ($status !== '') { $where[] = "i.status = :status"; $params[':status'] = $status; }

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
    "SELECT i.*, c.category_name, lo.location_name, lo.building, u.full_name AS reporter
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
    return BASE_URL . '/admin/reports.php?' . http_build_query($query);
}

$pageTitle = 'Manage reports';
require __DIR__ . '/../includes/admin_header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/dashboard.php">&larr; Back to dashboard</a></p>

<h1>Manage reports</h1>
<p class="muted">Every lost and found report from all users. Open one to change its status or delete it.</p>

<?php if (($_GET['done'] ?? '') === 'deleted'): ?>
  <div class="alert success">Report deleted.</div>
<?php endif; ?>

<!-- =================== -->
<!-- SEARCH + FILTERS -->
<!-- =================== -->
<form method="GET" class="filters card" style="grid-template-columns: 2fr 1fr 1fr;">
  <input name="q" placeholder="Search item, category, place or reporter" value="<?= e($q) ?>">

  <select name="type">
    <option value="">All types</option>
    <option value="Lost"  <?= $type === 'Lost'  ? 'selected' : '' ?>>Lost</option>
    <option value="Found" <?= $type === 'Found' ? 'selected' : '' ?>>Found</option>
  </select>

  <select name="status">
    <option value="">All statuses</option>
    <?php foreach ($statuses as $s): ?>
      <option value="<?= e($s) ?>" <?= $status === $s ? 'selected' : '' ?>><?= e($s) ?></option>
    <?php endforeach; ?>
  </select>

  <div class="filter-actions">
    <button class="btn small">Search</button>
    <a class="reset" href="<?= BASE_URL ?>/admin/reports.php">Reset</a>
  </div>
</form>

<p class="muted"><?= $total ?> report<?= $total === 1 ? '' : 's' ?> found</p>

<!-- =================== -->
<!-- RESULTS -->
<!-- =================== -->
<?php if (!$items): ?>
  <div class="card center muted">No reports match your search.</div>
<?php endif; ?>

<?php foreach ($items as $it): ?>
  <?php $isOpen = ($it['type'] === 'Lost' && $it['status'] === 'Lost')
               || ($it['type'] === 'Found' && $it['status'] === 'Available'); ?>
  <a class="card item" href="<?= BASE_URL ?>/admin/report_manage.php?type=<?= e($it['type']) ?>&amp;id=<?= (int)$it['item_id'] ?>">
    <?php if ($it['image']): ?>
      <img class="thumb" src="<?= BASE_URL ?>/uploads/<?= e($it['image']) ?>" alt="">
    <?php endif; ?>
    <div class="item-body">
      <div class="item-top">
        <span class="badge <?= strtolower($it['type']) ?>"><?= e($it['type']) ?></span>
        <h2><?= e($it['item_name']) ?></h2>
        <span class="badge <?= $isOpen ? 'open' : 'done' ?>"><?= e($it['status']) ?></span>
      </div>
      <p class="meta">
        Reported by <?= e($it['reporter']) ?> &middot;
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

<?php require __DIR__ . '/../includes/footer.php'; ?>