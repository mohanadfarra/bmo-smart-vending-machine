<?php
// iss/manager/customers.php
// Displays grouped customer data based on fuzzy matching.

require_once 'includes/header.php';
require_once 'includes/customer_matching.php'; // Include the matching functions

// Fetch completed transactions with customer details
$stmt = $conn->prepare("
    SELECT id, customer_name, customer_phone, total_amount, created_at
    FROM transactions
    WHERE status = 'completed'
      AND customer_name IS NOT NULL AND customer_name != '' AND customer_name != 'Card Payment at Machine'
      AND customer_phone IS NOT NULL AND customer_phone != '' AND customer_phone != 'N/A'
    ORDER BY created_at DESC
");
$stmt->execute();
$transactions = $stmt->get_result()->fetch_all(MYSQLI_ASSOC);

// Group customers using the matching logic
$customerGroups = groupCustomers($transactions);

?>

<div class="page-header">
    <h1>Customer Analytics</h1>
    <p>Identifying potentially returning customers based on name and phone number similarity.</p>
</div>

<?php if (isset($_SESSION['success'])): ?>
    <div class="alert alert-success">
        <?php
        echo $_SESSION['success'];
        unset($_SESSION['success']);
        ?>
    </div>
<?php endif; ?>

<?php if (isset($_SESSION['error'])): ?>
    <div class="alert alert-error">
        <?php
        echo $_SESSION['error'];
        unset($_SESSION['error']);
        ?>
    </div>
<?php endif; ?>

<div class="table-responsive">
    <?php if (empty($customerGroups)): ?>
        <div class="no-data">
            <p>No completed transactions with valid customer details found to analyze.</p>
        </div>
    <?php else: ?>
        <table class="data-table">
            <thead>
                <tr>
                    <th>Representative Name</th>
                    <th>Representative Phone</th>
                    <th>Total Purchases</th>
                    <th>Total Spent (TND)</th>
                    <th>First Purchase</th>
                    <th>Last Purchase</th>
                    <th>Associated Transactions</th> </tr>
            </thead>
            <tbody>
                <?php foreach ($customerGroups as $group): ?>
                <tr>
                    <td><?php echo htmlspecialchars($group['representative_name']); ?></td>
                    <td><?php echo htmlspecialchars($group['representative_phone']); ?></td>
                    <td><?php echo $group['total_purchases']; ?></td>
                    <td><?php echo number_format($group['total_spent'], 3); ?></td>
                    <td><?php echo $group['first_purchase_date']; ?></td>
                    <td><?php echo $group['last_purchase_date']; ?></td>
                    <td>
                        <?php
                        $tx_ids = array_map(function($tx) { return '#' . $tx['id']; }, $group['transactions']);
                        echo implode(', ', $tx_ids);
                        ?>
                        </td>
                </tr>
                <?php endforeach; ?>
            </tbody>
        </table>
    <?php endif; ?>
</div>

<?php require_once 'includes/footer.php'; ?>

