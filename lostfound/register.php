<?php
// !! Create Account  (flowchart: Already Registered? -> No -> Create Account)

require __DIR__ . '/includes/config.php';

// Already logged in? Nothing to register.
if (is_logged_in()) redirect('dashboard.php');

// ===================
// SETUP VARIABLES
// ===================
$errors = [];
$old = ['full_name' => '', 'email' => '', 'contact_number' => ''];

// ===================
// HANDLE FORM SUBMIT
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    }

    foreach ($old as $k => $_) {
        $old[$k] = trim($_POST[$k] ?? '');
    }
    // Passwords are NOT trimmed: spaces can be part of a password
    $password  = $_POST['password'] ?? '';
    $password2 = $_POST['password2'] ?? '';

    // Allow "0917 123 4567" or "0917-123-4567" but store only the digits
    $old['contact_number'] = preg_replace('/[\s-]/', '', $old['contact_number']);

    // ===================
    // VALIDATION
    // ===================
    if ($old['full_name'] === '') $errors[] = "Full name is required.";
    elseif (strlen($old['full_name']) > 100) $errors[] = "Full name must be 100 characters or less.";

    if ($old['email'] === '') {
        $errors[] = "Email is required.";
    } elseif (!filter_var($old['email'], FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Enter a valid email address.";
    } elseif (strlen($old['email']) > 100) {
        $errors[] = "Email must be 100 characters or less.";
    }

    // Contact number is optional, but if given it must look like a PH mobile number
    if ($old['contact_number'] !== '' && !preg_match('/^(09\d{9}|\+639\d{9})$/', $old['contact_number'])) {
        $errors[] = "Contact number must look like 09171234567.";
    }

    if (strlen($password) < 8) $errors[] = "Password must be at least 8 characters.";
    elseif ($password !== $password2) $errors[] = "Passwords do not match.";

    // Is the email already taken?
    if (!$errors) {
        $chk = $pdo->prepare("SELECT COUNT(*) FROM `User` WHERE email = ?");
        $chk->execute([$old['email']]);
        if ($chk->fetchColumn()) $errors[] = "That email is already registered. Try logging in.";
    }

    // ===================
    // INSERT (CREATE)
    // ===================
    if (!$errors) {
        try {
            $stmt = $pdo->prepare(
                "INSERT INTO `User` (full_name, email, password, contact_number)
                 VALUES (?, ?, ?, ?)"
            );
            $stmt->execute([
                $old['full_name'],
                $old['email'],
                password_hash($password, PASSWORD_DEFAULT),   // never store the plain password
                $old['contact_number'] !== '' ? $old['contact_number'] : null,
            ]);
            // Flowchart: Create Account -> Log In
            redirect('login.php?registered=1');
        } catch (PDOException $ex) {
            // 23000 = duplicate email slipped in between the check and the insert
            if ($ex->getCode() === '23000') {
                $errors[] = "That email is already registered. Try logging in.";
            } else {
                throw $ex;
            }
        }
    }
}

$pageTitle = 'Create account';
require __DIR__ . '/includes/header.php';
?>

<div class="card narrow">
  <h1>Create your account</h1>
  <p class="muted">You need an account to report items and submit claims.</p>

  <?php if ($errors): ?>
    <div class="alert error">
      <?php foreach ($errors as $err): ?>
        <?= e($err) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <?= csrf_field() ?>

    <label>Full name
      <input name="full_name" maxlength="100" autocomplete="name" value="<?= e($old['full_name']) ?>">
    </label>

    <label>Email
      <input type="email" name="email" maxlength="100" autocomplete="email" value="<?= e($old['email']) ?>">
    </label>

    <label>Contact number <span class="muted">(optional)</span>
      <input name="contact_number" maxlength="20" autocomplete="tel" placeholder="09171234567" value="<?= e($old['contact_number']) ?>">
    </label>

    <label>Password <span class="muted">(at least 8 characters)</span>
      <input type="password" name="password" autocomplete="new-password">
    </label>

    <label>Confirm password
      <input type="password" name="password2" autocomplete="new-password">
    </label>

    <button class="btn">Create account</button>
  </form>

  <p class="muted center">Already registered? <a href="<?= BASE_URL ?>/login.php">Log in</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>