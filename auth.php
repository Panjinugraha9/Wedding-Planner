<?php
// auth.php - Authentication Handler
session_start();

function isLoggedIn() {
    return isset($_SESSION['user_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: login.php");
        exit();
    }
}

function requireRole($required_role) {
    requireLogin();
    if ($_SESSION['user_role'] !== $required_role) {
        header("Location: unauthorized.php");
        exit();
    }
}

function logout() {
    session_destroy();
    header("Location: login.php");
    exit();
}