<?php
// Acts as a connector of php and sql

// ===================
// DEV SETTINGS
// ===================
ini_set('display_errors', 1);
error_reporting(E_ALL);

// ===================
// DATABASE CONNECTION
// ===================
$pdo = new PDO(
    "mysql:host=localhost;dbname=lostfound_pamp;charset=utf8mb4",
    "root",
    "",
    [
        PDO::ATTR_ERRMODE => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
    ]
);