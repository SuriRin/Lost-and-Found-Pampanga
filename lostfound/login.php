<?php
// !! Log In  (flowchart: Already Registered? -> Yes -> Log In -> User Dashboard)

require __DIR__ . '/includes/config.php';

if (is_logged_in()) redirect('dashboard.php');

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

        // Find the user by email (prepared statement = safe from SQL injection)
        $stmt = $pdo->prepare("SELECT user_id, full_name, password FROM `User` WHERE email = ?");
        $stmt->execute([$email]);
        $user = $stmt->fetch();

        // One vague message on purpose: don't reveal whether the email exists
        if (!$user || !password_verify($password, $user['password'])) {
            $errors[] = "Incorrect email or password.";
        } else {
            session_regenerate_id(true);          // new session id after login
            $_SESSION['user_id']   = (int)$user['user_id'];
            $_SESSION['user_name'] = $user['full_name'];
            redirect('dashboard.php');
        }
    }
}

$pageTitle = 'Log in';
require __DIR__ . '/includes/header.php';
?>

<div class="card narrow">
  <h1>Welcome back</h1>
  <p class="muted">Log in to report items and track your claims.</p>

  <?php if (isset($_GET['registered'])): ?>
    <div class="alert success">Account created. You can log in now.</div>
  <?php elseif (isset($_GET['loggedout'])): ?>
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

  <p class="muted center">New here? <a href="<?= BASE_URL ?>/register.php">Create an account</a></p>
</div>

<?php require __DIR__ . '/includes/footer.php'; ?>