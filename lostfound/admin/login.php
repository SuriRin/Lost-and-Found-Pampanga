<?php
// !! Admin Login
//    flowchart: START -> Open Admin Login -> Enter Login Credentials -> Credentials Correct?
//               Yes -> Admin Dashboard
//               No  -> Display Error Message -> back to the login form

require __DIR__ . '/../includes/config.php';

// Already logged in as an admin? Go straight to the dashboard.
if (is_admin_logged_in()) redirect('admin/dashboard.php');

// ===================
// SETUP VARIABLES
// ===================
$errors = [];
$email  = '';

// ===================
// HANDLE FORM SUBMIT
// ===================
if ($_SERVER['REQUEST_METHOD'] === 'POST') {

    if (!csrf_valid()) {
        $errors[] = "Your session expired. Please try again.";
    } else {
        $email    = trim($_POST['email'] ?? '');
        $password = $_POST['password'] ?? '';

        // Admins are in their own table (prepared statement = safe from SQL injection)
        $stmt = $pdo->prepare("SELECT admin_id, full_name, password FROM Admin WHERE email = ?");
        $stmt->execute([$email]);
        $admin = $stmt->fetch();

        // Flowchart: Credentials Correct? One vague message on purpose,
        // so it doesn't reveal whether the email exists.
        if (!$admin || !password_verify($password, $admin['password'])) {
            $errors[] = "Incorrect email or password.";      // No -> Display Error Message
        } else {
            session_regenerate_id(true);                      // new session id after login
            $_SESSION['admin_id']   = (int)$admin['admin_id'];
            $_SESSION['admin_name'] = $admin['full_name'];
            redirect('admin/dashboard.php');                  // Yes -> Admin Dashboard
        }
    }
}

$pageTitle = 'Admin log in';
require __DIR__ . '/../includes/admin_header.php';
?>

<div class="card narrow">
  <h1>Admin log in</h1>
  <p class="muted">For administrators only.</p>

  <?php if (isset($_GET['loggedout'])): ?>
    <div class="alert success">You have been logged out.</div>
  <?php endif; ?>

  <?php if ($errors): ?>
    <div class="alert error">
      <?php foreach ($errors as $err): ?>
        <?= e($err) ?><br>
      <?php endforeach; ?>
    </div>
  <?php endif; ?>

  <form method="POST" novalidate>
    <?= csrf_field() ?>

    <label>Email
      <input type="email" name="email" autocomplete="email" value="<?= e($email) ?>">
    </label>

    <label>Password
      <input type="password" name="password" autocomplete="current-password">
    </label>

    <button class="btn">Log in</button>
  </form>

  <p class="muted center"><a href="<?= BASE_URL ?>/login.php">&larr; User log in</a></p>
</div>

<?php require __DIR__ . '/../includes/footer.php'; ?>