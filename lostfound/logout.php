<?php
// !! Logout  (flowchart: Choose Action -> Logout -> END)
// Logging out changes state, so it only works from the POST form in the navigation bar.

require __DIR__ . '/includes/config.php';

if ($_SERVER['REQUEST_METHOD'] === 'POST' && csrf_valid()) {
    $_SESSION = [];                                   // forget everything
    if (ini_get('session.use_cookies')) {
        $p = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000, $p['path'], $p['domain'], $p['secure'], $p['httponly']);
    }
    session_destroy();
    redirect('login.php?loggedout=1');
}

// Opened directly (GET) or bad token: just send them somewhere sensible
redirect(is_logged_in() ? 'dashboard.php' : 'login.php');