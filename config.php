<?php
define('DB_HOST', 'localhost');
define('DB_NAME', 'ticket_booking');
define('DB_USER', 'root');
define('DB_PASS', '');

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS);
    $pdo->setAttribute(PDO::ATTR_ERRMODE, PDO::ERRMODE_EXCEPTION);
    $pdo->setAttribute(PDO::ATTR_DEFAULT_FETCH_MODE, PDO::FETCH_ASSOC);
} catch(PDOException $e) {
    die("[db] error" . $e->getMessage());
}
if (session_status() == PHP_SESSION_NONE) {
    session_start();
}

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header('Location: login.php');
        exit();
    }
}

function logout() {
    session_destroy();
    header('Location: index.php');
    exit();
}

function generateBookingReference() {
    return 'TKT' . date('Y') . strtoupper(substr(uniqid(), -8));
}

function formatPrice($price) {
    return number_format($price, 2) . ' ريال';
}

function formatDate($date) {
    $timestamp = strtotime($date);
    return date('d/m/Y', $timestamp) . ' - ' . date('H:i', $timestamp);
}
?>