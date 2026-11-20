<?php
// !! Admin Logout  (flowchart: Choose Action -> Logout -> END)
// Logging out changes state, so it only works from the POST form in the admin navigation bar.
// Only the admin part of the session is cleared, so a user login in the same browser is untouched.

require __DIR__ . '/../includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    unset($_SESSION['admin_id'], $_SESSION['admin_name']);
    session_regenerate_id(true);
    redirect('admin/login.php?loggedout=1');
}

// Opened directly (GET) or bad token: just send them somewhere sensible
redirect(is_admin_logged_in() ? 'admin/dashboard.php' : 'admin/login.php');