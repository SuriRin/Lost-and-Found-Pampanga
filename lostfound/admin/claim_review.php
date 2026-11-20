<?php
// !! Review One Claim
//    flowchart: Review Claim Details -> Approve Claim?
//               Yes -> Approve Claim -> Update Claim Status -> Update Item Status to Claimed
//               No  -> Reject Claim -> Update Claim Status to Rejected
//    Business rule 8: the admin reviews every claim, so admin_id records who decided.

require __DIR__ . '/../includes/config.php';
require_admin();

$aid = $_SESSION['admin_id'];
$id  = (int)($_GET['id'] ?? 0);

// ===================
// FETCH THE CLAIM WITH EVERYTHING THE ADMIN NEEDS TO JUDGE IT
// ===================
function fetch_claim(PDO $pdo, int $id) {
    $stmt = $pdo->prepare(
        "SELECT cl.claim_id, cl.found_item_id, cl.claim_description, cl.date_submitted,
                cl.claim_status, cl.admin_id,
                f.item_name, f.description AS item_description, f.date_found, f.image,
                f.status AS item_status,
                c.category_name, lo.location_name, lo.building,
                fu.full_name AS finder,
                u.full_name AS claimant, u.email AS claimant_email, u.contact_number AS claimant_phone,
                a.full_name AS admin_name
         FROM Claim cl
         JOIN Found_Item f ON f.found_item_id = cl.found_item_id
         JOIN Category c   ON c.category_id   = f.category_id
         JOIN Location lo  ON lo.location_id  = f.location_id
         JOIN `User` fu    ON fu.user_id      = f.user_id
         JOIN `User` u     ON u.user_id       = cl.user_id
         LEFT JOIN Admin a ON a.admin_id      = cl.admin_id
         WHERE cl.claim_id = ?"
    );
    $stmt->execute([$id]);
    return $stmt->fetch();
}

$claim = $id > 0 ? fetch_claim($pdo, $id) : false;

// ===================
// CLAIM NOT FOUND
// ===================
if (!$claim) {
    http_response_code(404);
    $pageTitle = 'Claim not found';
    require __DIR__ . '/../includes/admin_header.php';
    ?>
    <div class="card narrow center">
      <h1>Claim not found</h1>
      <p class="muted">That claim does not exist.</p>
      <p><a href="<?= BASE_URL ?>/admin/claims.php">&larr; Back to claims</a></p>
    </div>
    <?php
    require __DIR__ . '/../includes/footer.php';
    exit;
}

$error = null;

// ===================
// HANDLE APPROVE / REJECT
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    $action = $_POST['action'] ?? '';

    if (!csrf_valid()) {
        $error = "Your session expired. Please try again.";

    } elseif ($action === 'approve') {
        // Several tables change together, so it is one transaction:
        // either every step is saved or none of them is.
        $pdo->beginTransaction();
        try {
            // Lock the rows and check again, in case someone decided it a moment ago
            $lock = $pdo->prepare(
                "SELECT cl.claim_status, f.status AS item_status
                 FROM Claim cl
                 JOIN Found_Item f ON f.found_item_id = cl.found_item_id
                 WHERE cl.claim_id = ?
                 FOR UPDATE"
            );
            $lock->execute([$id]);
            $cur = $lock->fetch();

            if (!$cur || $cur['claim_status'] !== 'Pending') {
                $pdo->rollBack();
                $error = "This claim was already decided.";
            } elseif ($cur['item_status'] !== 'Available') {
                $pdo->rollBack();
                $error = "This item is no longer available, so the claim can't be approved.";
            } else {
                // Flowchart: Update Claim Status
                $pdo->prepare("UPDATE Claim SET claim_status = 'Approved', admin_id = ? WHERE claim_id = ?")
                    ->execute([$aid, $id]);

                // Flowchart: Update Item Status to Claimed
                $pdo->prepare("UPDATE Found_Item SET status = 'Claimed' WHERE found_item_id = ?")
                    ->execute([$claim['found_item_id']]);

                // Only one person can get the item, so the other pending claims on it are rejected
                $pdo->prepare(
                    "UPDATE Claim SET claim_status = 'Rejected', admin_id = ?
                     WHERE found_item_id = ? AND claim_id <> ? AND claim_status = 'Pending'"
                )->execute([$aid, $claim['found_item_id'], $id]);

                $pdo->commit();
                redirect('admin/claims.php?done=approved');
            }
        } catch (Throwable $ex) {
            if ($pdo->inTransaction()) $pdo->rollBack();
            throw $ex;
        }

    } elseif ($action === 'reject') {
        // Flowchart: Reject Claim -> Update Claim Status to Rejected
        $stmt = $pdo->prepare(
            "UPDATE Claim SET claim_status = 'Rejected', admin_id = ?
             WHERE claim_id = ? AND claim_status = 'Pending'"
        );
        $stmt->execute([$aid, $id]);

        if ($stmt->rowCount() === 0) {
            $error = "This claim was already decided.";
        } else {
            redirect('admin/claims.php?done=rejected');
        }
    }

    // Something stopped the action: show the page again with fresh data
    $claim = fetch_claim($pdo, $id);
}

// Other people waiting for the same item (useful when judging)
$stmt = $pdo->prepare(
    "SELECT COUNT(*) FROM Claim
     WHERE found_item_id = ? AND claim_id <> ? AND claim_status = 'Pending'"
);
$stmt->execute([$claim['found_item_id'], $id]);
$otherPending = (int)$stmt->fetchColumn();

$isPending  = $claim['claim_status'] === 'Pending';
$canApprove = $isPending && $claim['item_status'] === 'Available';

$pageTitle = 'Review claim';
require __DIR__ . '/../includes/admin_header.php';
?>

<p><a href="<?= BASE_URL ?>/admin/claims.php">&larr; Back to claims</a></p>

<?php if ($error): ?>
  <div class="alert error"><?= e($error) ?></div>
<?php endif; ?>

<!-- =================== -->
<!-- THE ITEM -->
<!-- =================== -->
<div class="card">
  <div class="item-top">
    <span class="badge found">Found item</span>
    <h1><?= e($claim['item_name']) ?></h1>
    <span class="badge <?= $claim['item_status'] === 'Available' ? 'open' : 'done' ?>"><?= e($claim['item_status']) ?></span>
  </div>

  <?php if ($claim['image']): ?>
    <img class="detail-img" src="<?= BASE_URL ?>/uploads/<?= e($claim['image']) ?>" alt="Photo of <?= e($claim['item_name']) ?>">
  <?php endif; ?>

  <dl class="details">
    <dt>Description</dt>
    <dd><?= $claim['item_description'] ? nl2br(e($claim['item_description'])) : '<span class="muted">No description given.</span>' ?></dd>

    <dt>Category</dt>
    <dd><?= e($claim['category_name']) ?></dd>

    <dt>Found at</dt>
    <dd><?= e($claim['location_name']) ?><?= $claim['building'] ? ' (' . e($claim['building']) . ')' : '' ?></dd>

    <dt>Date found</dt>
    <dd><?= e(date('F j, Y', strtotime($claim['date_found']))) ?></dd>

    <dt>Reported by</dt>
    <dd><?= e($claim['finder']) ?></dd>
  </dl>
</div>

<!-- =================== -->
<!-- THE CLAIM -->
<!-- =================== -->
<div class="card">
  <div class="item-top">
    <h2>Claim</h2>
    <span class="badge <?= e(strtolower($claim['claim_status'])) ?>"><?= e($claim['claim_status']) ?></span>
  </div>

  <dl class="details">
    <dt>Claimed by</dt>
    <dd><?= e($claim['claimant']) ?></dd>

    <dt>Email</dt>
    <dd><?= e($claim['claimant_email']) ?></dd>

    <dt>Contact number</dt>
    <dd><?= $claim['claimant_phone'] ? e($claim['claimant_phone']) : '<span class="muted">Not given</span>' ?></dd>

    <dt>Submitted</dt>
    <dd><?= e(date('F j, Y g:i A', strtotime($claim['date_submitted']))) ?></dd>

    <dt>Proof of ownership</dt>
    <dd><?= nl2br(e($claim['claim_description'])) ?></dd>
  </dl>

  <?php if ($otherPending > 0): ?>
    <p class="muted">
      <?= $otherPending ?> other pending claim<?= $otherPending === 1 ? '' : 's' ?> for this item.
      If you approve this one, the others are rejected automatically.
    </p>
  <?php endif; ?>
</div>

<!-- =================== -->
<!-- "APPROVE CLAIM?" BRANCH OF THE FLOWCHART -->
<!-- =================== -->
<div class="card">
  <?php if (!$isPending): ?>

    <h2>Already decided</h2>
    <p class="muted">
      This claim was <?= e(strtolower($claim['claim_status'])) ?><?= $claim['admin_name'] ? ' by ' . e($claim['admin_name']) : '' ?>.
    </p>

  <?php else: ?>

    <h2>Approve this claim?</h2>
    <?php if (!$canApprove): ?>
      <p class="muted">This item is already marked as <?= e(strtolower($claim['item_status'])) ?>, so the claim can only be rejected.</p>
    <?php else: ?>
      <p class="muted">Approving marks the item as claimed. Rejecting keeps the item available.</p>
    <?php endif; ?>

    <div style="display: flex; gap: 10px; margin-top: 14px;">
      <?php if ($canApprove): ?>
        <form method="POST" class="inline"
              onsubmit="return confirm('Approve this claim? The item will be marked as claimed.');">
          <?= csrf_field() ?>
          <input type="hidden" name="action" value="approve">
          <button class="btn small">Approve claim</button>
        </form>
      <?php endif; ?>

      <form method="POST" class="inline"
            onsubmit="return confirm('Reject this claim?');">
        <?= csrf_field() ?>
        <input type="hidden" name="action" value="reject">
        <button class="btn small" style="background: var(--danger);">Reject claim</button>
      </form>
    </div>

  <?php endif; ?>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>