<?php
// Shared top part of every ADMIN page. Set $pageTitle before including this file.
// Same look as includes/header.php, but with the admin navigation bar.
$pageTitle = $pageTitle ?? 'Admin';
?>
<!DOCTYPE html>
<html lang="en">
<head>
  <meta charset="UTF-8">
  <meta name="viewport" content="width=device-width, initial-scale=1">
  <title><?= e($pageTitle) ?> | Admin | Lost and Found Hub</title>
  <link rel="stylesheet" href="<?= BASE_URL ?>/assets/style.css">
</head>
<body>

<!-- =================== -->
<!-- ADMIN NAVIGATION BAR -->
<!-- =================== -->
<nav class="navbar">
  <a class="brand" href="<?= BASE_URL ?>/admin/dashboard.php">Lost and Found <span>Hub</span> <span class="badge">Admin</span></a>
  <div class="links">
    <?php if (is_admin_logged_in()): ?>
      <a href="<?= BASE_URL ?>/admin/dashboard.php">Dashboard</a>
      <a href="<?= BASE_URL ?>/admin/reports.php">Reports</a>
      <a href="<?= BASE_URL ?>/admin/claims.php">Claims</a>
      <span class="who"><?= e($_SESSION['admin_name']) ?></span>
      <form method="POST" action="<?= BASE_URL ?>/admin/logout.php" class="inline">
        <?= csrf_field() ?>
        <button class="linkbtn">Log out</button>
      </form>
    <?php endif; ?>
  </div>
</nav>

<main class="container">