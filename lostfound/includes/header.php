<?php
// Shared top part of every page. Set $pageTitle before including this file.
$pageTitle = $pageTitle ?? 'Lost and Found Hub';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Lost and Found Hub</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<!-- =================== -->
<!-- NAVIGATION BAR -->
<!-- =================== -->
<nav class="navbar">
  <a class="brand" href="<?= BASE_URL ?>/index.php">Lost and Found <span>Hub</span></a>
  <div class="links">
    <?php if (is_logged_in()): ?>
      <a href="<?= BASE_URL ?>/dashboard.php">Dashboard</a>
      <a href="<?= BASE_URL ?>/items.php">Browse items</a>
      <span class="who"><?= e($_SESSION['user_name']) ?></span>
      <form method="POST" action="<?= BASE_URL ?>/logout.php" class="inline">
        <?= csrf_field() ?>
        <button class="linkbtn">Log out</button>
      </form>
    <?php else: ?>
      <a href="<?= BASE_URL ?>/login.php">Log in</a>
      <a href="<?= BASE_URL ?>/register.php">Create account</a>
    <?php endif; ?>
  </div>
</nav>

<main class="container">