<?php
// Start the session if not already started


// Define the base path for includes
define('BASE_PATH', dirname(dirname(__FILE__)));

// Include the database configuration
require_once BASE_PATH . '/config/db.php';

// Include the authentication file
require_once BASE_PATH . '/includes/auth.php';

// Check if user is logged in
requireLogin();

?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Vending Machine Management</title>
    <link rel="stylesheet" href="assets/css/dashboard.css">
</head>
<body>
    <div class="dashboard">
        <aside class="sidebar">
            <div class="sidebar-header">
                <h2>Vending Manager</h2>
            </div>
            <nav class="sidebar-nav">
                <a href="dashboard.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'dashboard.php' ? 'active' : ''; ?>">
                    Dashboard
                </a>
                <a href="machines.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'machines.php' ? 'active' : ''; ?>">
                    Machines
                </a>
                <a href="products.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'products.php' ? 'active' : ''; ?>">
                    Products
                </a>
                <a href="transactions.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'transactions.php' ? 'active' : ''; ?>">
                    Transactions
                </a>
                <a href="reports.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'reports.php' ? 'active' : ''; ?>">
                    Reports
                </a>
                <a href="settings.php" class="nav-link <?php echo basename($_SERVER['PHP_SELF']) === 'settings.php' ? 'active' : ''; ?>">
                    Settings
                </a>
                <a href="logout.php" class="nav-link">
                    Logout
                </a>
            </nav>
        </aside>
        <main class="main-content">
