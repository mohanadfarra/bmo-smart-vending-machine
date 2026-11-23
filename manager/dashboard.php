<?php
require_once 'includes/header.php';

// Get quick statistics
$stats = [
    'total_machines' => $conn->query("SELECT COUNT(*) as count FROM vending_machines")->fetch_assoc()['count'],
    'active_machines' => $conn->query("SELECT COUNT(*) as count FROM vending_machines WHERE status = 'active'")->fetch_assoc()['count'],
    'low_stock_products' => $conn->query("SELECT COUNT(*) as count FROM products WHERE quantity <= min_stock_alert")->fetch_assoc()['count'],
    'total_revenue' => $conn->query("SELECT SUM(total_revenue) as total FROM vending_machines")->fetch_assoc()['total'] ?? 0
];

// Get recent transactions
$recent_transactions = $conn->query("
    SELECT t.*, v.location 
    FROM transactions t 
    JOIN vending_machines v ON t.machine_id = v.id 
    ORDER BY t.created_at DESC 
    LIMIT 5
");

// Get low stock alerts
$low_stock_items = $conn->query("
    SELECT p.*, v.location 
    FROM products p 
    JOIN vending_machines v ON p.machine_id = v.id 
    WHERE p.quantity <= p.min_stock_alert
    ORDER BY p.quantity ASC 
    LIMIT 5
");
?>

<div class="dashboard-header">
    <h1>Dashboard Overview</h1>

</div>

<div class="stats-grid">
    <div class="stat-card">
        <h3>Total Machines</h3>
        <div class="value"><?php echo $stats['total_machines']; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Active Machines</h3>
        <div class="value"><?php echo $stats['active_machines']; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Low Stock Alerts</h3>
        <div class="value"><?php echo $stats['low_stock_products']; ?></div>
    </div>
    
    <div class="stat-card">
        <h3>Total Revenue</h3>
        <div class="value"><?php echo number_format($stats['total_revenue'], 3); ?> TND</div>
    </div>
</div>

<div class="dashboard-grid">
    <!-- Low Stock Alerts -->
    <div class="dashboard-card">
        <h2>Low Stock Alerts</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>Product</th>
                        <th>Location</th>
                        <th>Quantity</th>
                        <th>Min Stock</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($item = $low_stock_items->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo htmlspecialchars($item['name']); ?></td>
                        <td><?php echo htmlspecialchars($item['location']); ?></td>
                        <td><?php echo $item['quantity']; ?></td>
                        <td><?php echo $item['min_stock_alert']; ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>

    <!-- Recent Transactions -->
    <div class="dashboard-card">
        <h2>Recent Transactions</h2>
        <div class="table-responsive">
            <table class="data-table">
                <thead>
                    <tr>
                        <th>ID</th>
                        <th>Location</th>
                        <th>Amount</th>
                        <th>Status</th>
                        <th>Date</th>
                    </tr>
                </thead>
                <tbody>
                    <?php while ($transaction = $recent_transactions->fetch_assoc()): ?>
                    <tr>
                        <td><?php echo $transaction['id']; ?></td>
                        <td><?php echo htmlspecialchars($transaction['location']); ?></td>
                        <td><?php echo number_format($transaction['total_amount'], 3); ?> TND</td>
                        <td>
                            <span class="status-badge status-<?php echo $transaction['status']; ?>">
                                <?php echo ucfirst($transaction['status']); ?>
                            </span>
                        </td>
                        <td><?php echo date('Y-m-d H:i', strtotime($transaction['created_at'])); ?></td>
                    </tr>
                    <?php endwhile; ?>
                </tbody>
            </table>
        </div>
    </div>
</div>

<?php require_once 'includes/footer.php'; ?>