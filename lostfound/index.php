<?php
// START of the flowchart: "Open Website"
// Logged-in users go to their dashboard, everyone else goes to the login page.
require __DIR__ . '/includes/config.php';

redirect(is_logged_in() ? 'dashboard.php' : 'login.php');