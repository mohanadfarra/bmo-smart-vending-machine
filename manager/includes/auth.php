<?php
session_start();

function isLoggedIn() {
    return isset($_SESSION['manager_id']);
}

function requireLogin() {
    if (!isLoggedIn()) {
        header("Location: index.php");
        exit();
    }
}

function isAdmin() {
    return isset($_SESSION['manager_role']) && $_SESSION['manager_role'] === 'admin';
}

function requireAdmin() {
    requireLogin();
    if (!isAdmin()) {
        header("Location: dashboard.php");
        exit();
    }
}

function logout() {
    session_start();
    session_destroy();
    header("Location: index.php");
    exit();
}
